<?php

namespace Database\Seeders;

use App\Models\DailyItem;
use App\Models\Delivery;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Traits\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    use Generator;

    /** Days of past transactions to create, counting back from yesterday. */
    private const DAYS = 5;

    /** Transactions per day. */
    private const PER_DAY = 15;

    /** Most different items in one transaction. */
    private const MAX_ITEMS = 5;

    /** Where deliveries go. */
    private const ADDRESSES = [
        'Blk 4 Lot 12 Sampaguita St., Brgy. San Isidro',
        '27 Mabini Ave., Brgy. Poblacion',
        'Unit 3B Rosal Apartments, Brgy. Santa Cruz',
        '118 Narra St., Brgy. Bagong Silang',
        'Purok 5, Brgy. Maligaya',
        '9 Acacia Lane, Greenview Subdivision',
        '56 Rizal St., Brgy. Del Pilar',
        'Phase 2 Block 7 Lot 3, Villa Esperanza',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($daysAgo = 1; $daysAgo <= self::DAYS; $daysAgo++) {
            $date = today()->subDays($daysAgo);
            $menu = DailyItem::whereDate('date', $date)->get();

            // Nothing to sell, or already seeded: leave the day alone.
            if ($menu->isEmpty() || Transaction::whereDate('transaction_date', $date)->exists()) {
                continue;
            }

            DB::transaction(function () use ($date, $menu) {
                for ($i = 0; $i < self::PER_DAY; $i++) {
                    $this->createTransaction($date, $menu);
                }
            });
        }
    }

    private function createTransaction(Carbon $date, Collection $menu): void
    {
        $when = $date->copy()->setTime(random_int(9, 18), random_int(0, 59), random_int(0, 59));

        // 1 to MAX_ITEMS different items from that day's menu, a few of each.
        $lines = $menu->random(min(random_int(1, self::MAX_ITEMS), $menu->count()))
            ->map(function (DailyItem $dailyItem) {
                $quantity = Arr::random([1, 1, 1, 2, 2, 3]);

                return [
                    'daily_item_id' => $dailyItem->id,
                    'unit_price' => $dailyItem->price,
                    'quantity' => $quantity,
                    'subtotal' => round($dailyItem->price * $quantity, 2),
                ];
            });

        $total = $lines->sum('subtotal');

        // Most are paid; the rest were cancelled, refunded or are still pending.
        $roll = random_int(1, 100);
        [$status, $paymentStatus] = match (true) {
            $roll <= 80 => ['COMPLETED', 'PAID'],
            $roll <= 88 => ['CANCELLED', 'CANCELLED'],
            $roll <= 94 => ['REFUNDED', 'REFUNDED'],
            default => ['PENDING', 'PENDING'],
        };

        $type = random_int(1, 100) <= 80 ? 'WALK IN' : 'DELIVERY';

        $transaction = $this->saveAt(new Transaction([
            'reference_number' => $this->generateReferenceNumber(),
            'total' => $total,
            'type' => $type,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'transaction_date' => $when,
        ]), $when);

        foreach ($lines as $line) {
            $this->saveAt(new TransactionItem($line + ['transaction_id' => $transaction->id]), $when);
        }

        // Deliveries follow their transaction: delivered once completed, called off with a cancellation.
        if ($type === 'DELIVERY') {
            $this->saveAt(new Delivery([
                'transaction_id' => $transaction->id,
                'address' => Arr::random(self::ADDRESSES),
                'status' => match ($status) {
                    'COMPLETED', 'REFUNDED' => 'DELIVERED',
                    'CANCELLED' => 'CANCELLED',
                    default => 'PENDING',
                },
            ]), $when);
        }

        // Paid and refunded transactions have a payment (cancelled and pending ones don't).
        if (in_array($status, ['COMPLETED', 'REFUNDED'], true)) {
            $method = Arr::random(['CASH', 'CASH', 'CASH', 'CASH', 'CASH', 'GCASH', 'GCASH', 'BANK TRANSFER']);

            // Cash is handed over in round amounts, so there's change; the rest is exact.
            $amount = $method === 'CASH' ? ceil($total / 50) * 50 : $total;

            $this->saveAt(new TransactionPayment([
                'transaction_id' => $transaction->id,
                'payment_amount' => $amount,
                'total_balance' => $total,
                'outstanding_balance' => $total - $amount,
                'payment_method' => $method,
            ]), $when);
        }
    }

    /** Save a model with its timestamps set to the given moment instead of "now". */
    private function saveAt(Model $model, Carbon $when): Model
    {
        $model->created_at = $model->updated_at = $when;
        $model->save();

        return $model;
    }
}
