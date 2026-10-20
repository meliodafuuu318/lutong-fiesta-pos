<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DailyItem;
use App\Models\Item;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $menuByCategory = DailyItem::todayMenu()
            ->with('item')
            ->get()
            ->sortBy(fn (DailyItem $dailyItem) => $dailyItem->item->name)
            ->groupBy(fn (DailyItem $dailyItem) => $dailyItem->item->category_id);

        // Only categories that have something on today's menu get a tab.
        $categories = Category::whereIn('id', $menuByCategory->keys())
            ->orderBy('id')
            ->get();

        // Active items that aren't on today's menu yet, grouped by category for the "add item" dropdown.
        $addableItems = Item::with('category')
            ->where('is_active', true)
            ->whereNotIn('id', DailyItem::todayMenu()->select('item_id'))
            ->orderBy('category_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Item $item) => $item->category->name);

        return view('dashboard', compact('categories', 'menuByCategory', 'addableItems'));
    }
}
