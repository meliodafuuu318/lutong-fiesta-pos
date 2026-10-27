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
     * (daily_item_id + quantity or unit_price), and address (DELIVERY) or
     * payment_method and payment_amount (WALK IN). Everything is saved or nothing is.
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

                $quantity = $item['quantity'] ?? null;
                $unitPrice = $quantity === null
                    ? (float) $item['unit_price']
                    : (float) $dailyItem->price;
                $amount = $unitPrice * ($quantity ?? 1);
                $totalAmount += $amount;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'daily_item_id' => $dailyItem->id,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
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

                $this->syncOverallStatus($transaction);
            }

            return $transaction;
        });
    }

    /**
     * Record a payment on a transaction that isn't fully paid yet.
     *
     * Same rules as the API: a payment that covers the balance (or more, in which
     * case the rest is change) marks the transaction COMPLETED and PAID.
     *
     * @throws TransactionException when the transaction can't take a payment
     */
    public function addPayment(Transaction $transaction, string $method, float|int|string $amount): TransactionPayment
    {
        return DB::transaction(function () use ($transaction, $method, $amount) {
            // Locked so that two people paying at once can't both see the old balance.
            $transaction = Transaction::whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($transaction->isVoid()) {
                throw new TransactionException('This transaction was cancelled or refunded, so it can\'t take payments.');
            }

            if ($transaction->payment_status === 'PAID') {
                throw new TransactionException('This transaction is already fully paid.');
            }

            $outstandingBalance = round(
                (float) $transaction->total
                - (float) $transaction->transactionPayments()->sum('payment_amount')
                - (float) $amount,
                2,
            );

            $payment = TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'payment_method' => $method,
                'payment_amount' => $amount,
                'outstanding_balance' => $outstandingBalance,
                'total_balance' => $transaction->total,
            ]);

            if ($outstandingBalance <= 0) {
                $transaction->update(['payment_status' => 'PAID']);
            }

            $this->syncOverallStatus($transaction);

            return $payment;
        });
    }

    /**
     * Mark a pending delivery as delivered.
     *
     * @throws TransactionException when there is no pending delivery to mark
     */
    public function markDelivered(Transaction $transaction): Delivery
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = Transaction::whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($transaction->isVoid()) {
                throw new TransactionException('This transaction was cancelled or refunded, so it can\'t be delivered.');
            }

            $delivery = $transaction->delivery;

            if (! $delivery) {
                throw new TransactionException('This transaction has no delivery.');
            }

            if ($delivery->status !== 'PENDING') {
                throw new TransactionException('This delivery is no longer pending.');
            }

            $delivery->update(['status' => 'DELIVERED']);
            $this->syncOverallStatus($transaction);

            return $delivery;
        });
    }

    public function cancel(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = Transaction::with('delivery')->lockForUpdate()->findOrFail($transaction->getKey());

            if ($transaction->isVoid()) {
                throw new TransactionException('This transaction is already cancelled or refunded.');
            }

            $transaction->update([
                'status' => 'CANCELLED',
                'payment_status' => 'CANCELLED',
            ]);
            $transaction->delivery?->update(['status' => 'CANCELLED']);

            return $transaction->refresh();
        });
    }

    public function refund(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = Transaction::with('delivery')->lockForUpdate()->findOrFail($transaction->getKey());

            if ($transaction->isVoid()) {
                throw new TransactionException('This transaction is already cancelled or refunded.');
            }

            $transaction->update([
                'status' => 'REFUNDED',
                'payment_status' => 'REFUNDED',
            ]);
            $transaction->delivery?->update(['status' => 'REFUNDED']);

            return $transaction->refresh();
        });
    }

    public function syncOverallStatus(Transaction $transaction): void
    {
        $transaction->refresh()->load('delivery');
        $paymentComplete = $transaction->payment_status === 'PAID';
        $deliveryComplete = $transaction->type !== 'DELIVERY'
            || $transaction->delivery?->status === 'DELIVERED';

        if ($paymentComplete && $deliveryComplete) {
            $transaction->update(['status' => 'COMPLETED']);
        } else {
            $transaction->update(['status' => 'PENDING']);
        }
    }
}
