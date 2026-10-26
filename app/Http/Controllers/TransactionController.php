<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Delivery,
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
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(IndexTransactionRequest $request) {
        $transactions = Transaction::query()
            ->with('delivery')
            ->withSum('transactionPayments', 'payment_amount');

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

        $transactions = $transactions->get();

        return $this->success('Transactions retrieved successfully', TransactionResource::collection($transactions), 200);
    }

    public function store(StoreTransactionRequest $request, TransactionService $transactions) {
        try {
            $transaction = $transactions->create($request->validated());
        } catch (\Exception $e) {
            return $this->error('Failed to create transaction: ' . $e->getMessage(), 500);
        }

        $transaction->load('delivery');

        return $this->success('Transaction created successfully', new TransactionResource($transaction), 201);
    }

    public function show(Transaction $transaction) {
        $transaction->load('transactionItems', 'transactionPayments', 'delivery');
        return $this->success('Transaction retrieved successfully', new TransactionResource($transaction), 200);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction) {
        if ($transaction->status === 'CANCELLED' || $transaction->status === 'REFUNDED') {
            return $this->error('Cannot update a cancelled or refunded transaction', 400);
        }

        $updatesDelivery = $request->filled('address') || $request->filled('delivery_status');

        if ($updatesDelivery) {
            if ($transaction->type !== 'DELIVERY') {
                return $this->error('Only delivery transactions have delivery details', 400);
            }

            // Delivery transactions made before deliveries existed have no delivery yet.
            if (!$transaction->delivery && !$request->filled('address')) {
                return $this->error('A delivery address is required', 400);
            }
        }

        DB::beginTransaction();

        try {
            if ($updatesDelivery) {
                $deliveryData = array_filter([
                    'address' => $request->address,
                    'status' => $request->delivery_status,
                ]);

                if ($transaction->delivery) {
                    $transaction->delivery->update($deliveryData);
                } else {
                    Delivery::create($deliveryData + ['transaction_id' => $transaction->id]);
                }
            }

            if ($request->filled('payment_method') && $request->filled('payment_amount')) {
                $totalBalance = $transaction->total;
                $outstandingBalance = $transaction->total - $transaction->transactionPayments()->sum('payment_amount') - $request->payment_amount;

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

            $transaction->refresh()->load('delivery');

            return $this->success('Transaction updated successfully', new TransactionResource($transaction), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    public function cancelTransaction(Transaction $transaction) {
        if ($transaction->status === 'CANCELLED' || $transaction->status === 'REFUNDED') {
            return $this->error('Transaction is already cancelled or refunded', 400);
        }

        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'CANCELLED', 'payment_status' => 'CANCELLED']);

            // A delivery that hasn't happened yet is called off together with the order.
            if ($transaction->delivery?->status === 'PENDING') {
                $transaction->delivery->update(['status' => 'CANCELLED']);
            }
        });

        $transaction->load('delivery');

        return $this->success('Transaction cancelled successfully', new TransactionResource($transaction), 200);
    }

    public function refundTransaction(Transaction $transaction) {
        if ($transaction->status === 'REFUNDED' || $transaction->status === 'CANCELLED') {
            return $this->error('Transaction is already refunded or cancelled', 400);
        }

        if (!$transaction->transactionPayments()->exists()) {
            return $this->error('Cannot refund a transaction with no payments', 400);
        }

        $transaction->update(['status' => 'REFUNDED', 'payment_status' => 'REFUNDED']);

        return $this->success('Transaction refunded successfully', new TransactionResource($transaction), 200);
    }
}
