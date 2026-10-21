<?php

namespace Database\Seeders;

use App\Models\DailyItem;
use App\Models\Item;
use Illuminate\Database\Seeder;

class DailyItemSeeder extends Seeder
{
    /** Days of past menus to create, counting back from yesterday. */
    private const DAYS = 5;

    /** How many items of each category end up on a day's menu: [min, max]. */
    private const PER_CATEGORY = [
        'Food' => [7, 11],
        'Beverages' => [2, 5],
        'Add-ons' => [1, 3],
    ];

    /** Chance, in percent, that an item on a past menu ended the day sold out. */
    private const SOLD_OUT_CHANCE = 25;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemsByCategory = Item::with('category')
            ->where('is_active', true)
            ->get()
            ->groupBy(fn (Item $item) => $item->category->name);

        for ($daysAgo = 1; $daysAgo <= self::DAYS; $daysAgo++) {
            $date = today()->subDays($daysAgo);

            // A day that already has a menu is left alone, so re-running never
            // duplicates anything (or deletes anything that has transactions).
            if (DailyItem::whereDate('date', $date)->exists()) {
                continue;
            }

            foreach (self::PER_CATEGORY as $category => [$min, $max]) {
                $items = $itemsByCategory->get($category, collect())
                    ->shuffle()
                    ->take(random_int($min, $max));

                foreach ($items as $item) {
                    $dailyItem = new DailyItem([
                        'item_id' => $item->id,
                        'price' => $item->default_price,
                        'date' => $date,
                        'status' => random_int(1, 100) <= self::SOLD_OUT_CHANCE ? 'SOLD OUT' : 'AVAILABLE',
                    ]);

                    // Menus are put together in the morning of their day.
                    $dailyItem->created_at = $dailyItem->updated_at = $date->copy()->setTime(7, 0);
                    $dailyItem->save();
                }
            }
        }
    }
}
