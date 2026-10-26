<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyItem\WebStoreDailyItemRequest;
use App\Http\Requests\DailyItem\WebUpdateDailyItemRequest;
use App\Models\DailyItem;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;

class DailyItemController extends Controller
{
    public function store(WebStoreDailyItemRequest $request): RedirectResponse
    {
        $item = Item::findOrFail($request->validated('item_id'));

        if (DailyItem::todayMenu()->where('item_id', $item->id)->exists()) {
            return redirect()->route('menu')
                ->withErrors(['daily_item' => "{$item->name} is already on today's menu."]);
        }

        DailyItem::create([
            'item_id' => $item->id,
            'price' => $request->validated('price'),
            'date' => today(),
            'status' => 'AVAILABLE',
        ]);

        return redirect()->to(route('menu') . '#tab-' . $item->category_id)
            ->with('status', "{$item->name} added to today's menu.");
    }

    public function update(WebUpdateDailyItemRequest $request, DailyItem $dailyItem): RedirectResponse
    {
        if (! $dailyItem->date->isToday()) {
            return $this->backToMenu($dailyItem)
                ->withErrors(['daily_item' => 'Only items on today\'s menu can be changed.']);
        }

        $dailyItem->update($request->validated());

        return $this->backToMenu($dailyItem)
            ->with('status', "{$dailyItem->item->name} updated.");
    }

    public function destroy(DailyItem $dailyItem): RedirectResponse
    {
        $name = $dailyItem->item->name;

        if ($dailyItem->transactionItems()->exists()) {
            return $this->backToMenu($dailyItem)->withErrors([
                'daily_item' => "{$name} already has orders, so it can't be removed. Mark it as sold out instead.",
            ]);
        }

        if (! $dailyItem->date->isToday()) {
            return $this->backToMenu($dailyItem)
                ->withErrors(['daily_item' => 'Only items on today\'s menu can be removed.']);
        }

        $dailyItem->delete();

        return $this->backToMenu($dailyItem)
            ->with('status', "{$name} removed from today's menu.");
    }

    /** Back to the menu page, on the tab the item lives in. */
    private function backToMenu(DailyItem $dailyItem): RedirectResponse
    {
        return redirect()->to(route('menu') . '#tab-' . $dailyItem->item->category_id);
    }
}
