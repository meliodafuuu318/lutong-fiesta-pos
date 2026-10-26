<?php

namespace App\Services;

use App\Exceptions\TransactionException;
use App\Models\DailyItem;
use App\Models\Delivery;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Traits\Generator;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    use Generator;

    /**
     * Create a transaction with its items, and its delivery or first payment.
     *
     * $data is a validated StoreTransactionRequest: type, transaction_items
     * (daily_item_id + quantity), and address (DELIVERY) or payment_method and
     * payment_amount (WALK IN). Everything is saved or nothing is.
     *
     * @throws TransactionException when an item isn't on today's available menu
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'reference_number' => $this->generateReferenceNumber(),
                'total' => 0,
                'type' => $data['type'],
                'status' => 'PENDING',
                'payment_status' => 'PENDING',
                'transaction_date' => now(),
            ]);

            $totalAmount = 0;

            foreach ($data['transaction_items'] as $item) {
                $dailyItem = DailyItem::todayMenu()->available()->where('id', $item['daily_item_id'])->first();

                if (!$dailyItem) {
                    throw new TransactionException(
                        'Daily item not available for item_id: ' . $item['daily_item_id'],
                        (int) $item['daily_item_id'],
                    );
                }

                $amount = $dailyItem->price * $item['quantity'];
                $totalAmount += $amount;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'daily_item_id' => $dailyItem->id,
                    'unit_price' => $dailyItem->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $amount,
                ]);
            }

            $transaction->update(['total' => $totalAmount]);

            if ($data['type'] === 'DELIVERY') {
                Delivery::create([
                    'transaction_id' => $transaction->id,
                    'address' => $data['address'],
                    'status' => 'PENDING',
                ]);
            }

            if ($data['type'] === 'WALK IN' && isset($data['payment_method'])) {
                $totalBalance = $transaction->total;
                $outstandingBalance = $totalBalance - $data['payment_amount'];

                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $data['payment_method'],
                    'payment_amount' => $data['payment_amount'],
                    'outstanding_balance' => $outstandingBalance,
                    'total_balance' => $totalBalance,
                ]);

                if ($outstandingBalance <= 0) {
                    $transaction->update([
                        'status' => 'COMPLETED',
                        'payment_status' => 'PAID',
                    ]);
                }
            }

            return $transaction;
        });
    }
}
