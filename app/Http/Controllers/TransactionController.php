<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Transaction,
    TransactionItem,
    TransactionPayment,
    DailyItem
};
use App\Http\Requests\Transaction\{
    StoreTransactionRequest,
    UpdateTransactionRequest,
    IndexTransactionRequest
};

class TransactionController extends Controller
{
    public function index(IndexTransactionRequest $request) {
        $transactions = Transaction::query();

        if ($request->filled('search')) {
            $transactions->where('reference_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('type')) {
            $transactions->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $transactions->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $transactions->whereDate('created_at', '<=', $request->date_to);
        }

        return $this->success('Transactions retrieved successfully', $transactions, 200);
    }

    public function store(StoreTransactionRequest $request) {
        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'reference_number' => $this->generateReferenceNumber(),
                'total' => 0,
                'type' => $request->type,
                'status' => 'PENDING',
                'payment_status' => 'PENDING',
            ]);

            $totalAmount = 0;

            foreach ($request->input('transaction_items') as $item) {
                $dailyItem = DailyItem::find($item['daily_item_id']);
                if (!$dailyItem) {
                    throw new \Exception('Item not in today\'s menu');
                }

                $amount = $dailyItem->price * $item['quantity'];
                $totalAmount += $amount;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'daily_item_id' => $dailyItem->id,
                    'unit_price' => $dailyItem->price,
                    'quantity' => $item['quantity'],
                    'sub_total' => $amount
                ]);
            }

            $transaction->update(['total' => $totalAmount]);

            if ($request->type === 'WALK IN' && $request->has('payment_method')) {
                $totalBalance = $transaction->total;
                $outstandingBalance = $totalBalance - $request->payment_amount;

                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $request->payment_method,
                    'payment_amount' => $request->payment_amount,
                    'outstanding_balance' => $outstandingBalance,
                    'total_balance' => $totalBalance
                ]);

                if ($outstandingBalance <= 0) {
                    $transaction->update([
                        'status' => 'COMPLETED',
                        'payment_status' => 'PAID'
                    ]);
                }
            }

            DB::commit();

            return $this->success('Transaction created successfully', $transaction->load('transactionItems', 'transactionPayments'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create transaction: ' . $e->getMessage(), 500);
        }
    }

    public function show(Transaction $transaction) {
        $transaction->load('transactionItems', 'transactionPayments');
        return $this->success('Transaction retrieved successfully', $transaction, 200);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction) {
        if ($transaction->status === 'CANCELLED' || $transaction->status === 'REFUNDED') {
            return $this->error('Cannot update a cancelled or refunded transaction', 400);
        }

        DB::beginTransaction();

        try {
            if ($request->filled('payment_method') && $request->filled('payment_amount')) {
                $totalBalance = $transaction->total;
                $outstandingBalance = $totalBalance - $request->payment_amount;

                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $request->payment_method,
                    'payment_amount' => $request->payment_amount,
                    'outstanding_balance' => $outstandingBalance,
                    'total_balance' => $totalBalance
                ]);

                if ($outstandingBalance <= 0) {
                    $transaction->update([
                        'status' => 'COMPLETED',
                        'payment_status' => 'PAID'
                    ]);
                }
            }

            DB::commit();

            return $this->success('Transaction updated successfully', $transaction->load('transactionItems', 'transactionPayments'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    public function cancelTransaction(Transaction $transaction) {
        if ($transaction->status === 'CANCELLED' || $transaction->status === 'REFUNDED') {
            return $this->error('Transaction is already cancelled or refunded', 400);
        }

        $transaction->update(['status' => 'CANCELLED', 'payment_status' => 'CANCELLED']);

        return $this->success('Transaction cancelled successfully', $transaction, 200);
    }

    public function refundTransaction(Transaction $transaction) {
        if ($transaction->status === 'REFUNDED' || $transaction->status === 'CANCELLED') {
            return $this->error('Transaction is already refunded or cancelled', 400);
        }

        $transaction->update(['status' => 'REFUNDED', 'payment_status' => 'REFUNDED']);

        return $this->success('Transaction refunded successfully', $transaction, 200);
    }
}
