<?php

namespace Tests\Helpers;

use App\Models\Category;
use App\Models\DailyItem;
use App\Models\Delivery;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Database\Eloquent\Model;

/** Small builders for the transaction data the backup tests need. */
class BackupData
{
    private static int $reference = 0;

    public static function menuItem(string $name = 'Adobo', float $price = 120.00, string $date = '2026-10-27'): DailyItem
    {
        $category = Category::firstOrCreate(['name' => 'Mains']);

        $item = Item::create([
            'category_id' => $category->id,
            'name' => $name,
            'default_price' => $price,
            'is_active' => true,
        ]);

        return DailyItem::create([
            'item_id' => $item->id,
            'price' => $price,
            'date' => $date,
            'status' => 'AVAILABLE',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    public static function transaction(array $attributes = []): Transaction
    {
        $reference = 'LF-'.str_pad((string) ++self::$reference, 6, '0', STR_PAD_LEFT);

        return Model::unguarded(fn () => Transaction::create(array_merge([
            'reference_number' => $reference,
            'total' => 0,
            'type' => 'WALK IN',
            'status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'transaction_date' => '2026-10-27 12:00:00',
        ], $attributes)));
    }

    public static function line(Transaction $transaction, DailyItem $dailyItem, ?int $quantity, float $unitPrice): TransactionItem
    {
        return TransactionItem::create([
            'transaction_id' => $transaction->id,
            'daily_item_id' => $dailyItem->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * ($quantity ?? 1),
        ]);
    }

    public static function payment(Transaction $transaction, float $amount, string $method = 'CASH'): TransactionPayment
    {
        return TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'payment_method' => $method,
            'payment_amount' => $amount,
            'total_balance' => $transaction->total,
            'outstanding_balance' => (float) $transaction->total - $amount,
        ]);
    }

    public static function delivery(Transaction $transaction, string $address, string $status = 'PENDING'): Delivery
    {
        return Delivery::create([
            'transaction_id' => $transaction->id,
            'address' => $address,
            'status' => $status,
        ]);
    }

    /** A paid walk in transaction of one line, the quickest way to have something to export. */
    public static function simpleSale(?DailyItem $dailyItem = null, int $quantity = 2): Transaction
    {
        $dailyItem ??= self::menuItem();
        $transaction = self::transaction(['total' => $dailyItem->price * $quantity]);
        self::line($transaction, $dailyItem, $quantity, (float) $dailyItem->price);
        self::payment($transaction, (float) $dailyItem->price * $quantity);

        return $transaction;
    }
}
