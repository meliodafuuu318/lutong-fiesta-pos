<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DailyItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MenuService
{
    /**
     * Today's menu grouped by category, for the screens that take orders.
     * Sold out items are included (so they can be shown), but can't be picked.
     *
     * @return array{categories: Collection<int, Category>, menuByCategory: Collection<int, Collection<int, DailyItem>>}
     */
    public function today(): array
    {
        return $this->forDate(today());
    }

    /**
     * The menu of a given day, for editing an order taken on that day. $alsoIds adds menu entries
     * that are already on the order, so they can be shown whatever day they belong to.
     *
     * @param  array<int, int>  $alsoIds
     * @return array{categories: Collection<int, Category>, menuByCategory: Collection<int, Collection<int, DailyItem>>}
     */
    public function forDate(CarbonInterface $date, array $alsoIds = []): array
    {
        $menuByCategory = DailyItem::query()
            ->where(fn ($query) => $query->onDate($date)->orWhereIn('id', $alsoIds))
            ->with('item')
            ->get()
            ->sortBy(fn (DailyItem $dailyItem) => [$dailyItem->status === 'SOLD OUT' ? 1 : 0, $dailyItem->item->name])
            ->groupBy(fn (DailyItem $dailyItem) => $dailyItem->item->category_id);

        $categories = Category::whereIn('id', $menuByCategory->keys())
            ->orderBy('id')
            ->get();

        return compact('categories', 'menuByCategory');
    }
}
