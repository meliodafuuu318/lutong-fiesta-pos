<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DailyItem;
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

        return view('dashboard', compact('categories', 'menuByCategory'));
    }
}
