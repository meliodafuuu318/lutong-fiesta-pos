<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyItem\WebStoreDailyItemRequest;
use App\Http\Requests\DailyItem\WebStoreManyDailyItemRequest;
use App\Http\Requests\DailyItem\WebUpdateDailyItemRequest;
use App\Models\DailyItem;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

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

    /** Adds several items at once, each at its default price. */
    public function storeMany(WebStoreManyDailyItemRequest $request): RedirectResponse
    {
        $requested = collect($request->validated('item_ids'))->map(fn ($id) => (int) $id)->unique();

        $alreadyOnMenu = DailyItem::todayMenu()->whereIn('item_id', $requested)->pluck('item_id');

        $items = Item::whereIn('id', $requested->diff($alreadyOnMenu))->orderBy('category_id')->orderBy('name')->get();

        if ($items->isEmpty()) {
            return redirect()->route('menu')
                ->withErrors(['daily_item' => 'The selected items are already on today\'s menu.']);
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                DailyItem::create([
                    'item_id' => $item->id,
                    'price' => $item->default_price,
                    'date' => today(),
                    'status' => 'AVAILABLE',
                ]);
            }
        });

        $message = $items->count() === 1
            ? "{$items->first()->name} added to today's menu."
            : "{$items->count()} items added to today's menu.";

        if ($alreadyOnMenu->isNotEmpty()) {
            $message .= " {$alreadyOnMenu->count()} already on the menu " . ($alreadyOnMenu->count() === 1 ? 'was' : 'were') . ' skipped.';
        }

        return redirect()->to(route('menu') . '#tab-' . $items->first()->category_id)
            ->with('status', $message);
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
