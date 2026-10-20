<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyItem\WebUpdateDailyItemRequest;
use App\Models\DailyItem;
use Illuminate\Http\RedirectResponse;

class DailyItemController extends Controller
{
    public function update(WebUpdateDailyItemRequest $request, DailyItem $dailyItem): RedirectResponse
    {
        if (! $dailyItem->date->isToday()) {
            return $this->backToDashboard($dailyItem)
                ->withErrors(['daily_item' => 'Only items on today\'s menu can be changed.']);
        }

        $dailyItem->update($request->validated());

        return $this->backToDashboard($dailyItem)
            ->with('status', "{$dailyItem->item->name} updated.");
    }

    public function destroy(DailyItem $dailyItem): RedirectResponse
    {
        $name = $dailyItem->item->name;

        if ($dailyItem->transactionItems()->exists()) {
            return $this->backToDashboard($dailyItem)->withErrors([
                'daily_item' => "{$name} already has orders, so it can't be removed. Mark it as sold out instead.",
            ]);
        }

        if (! $dailyItem->date->isToday()) {
            return $this->backToDashboard($dailyItem)
                ->withErrors(['daily_item' => 'Only items on today\'s menu can be removed.']);
        }

        $dailyItem->delete();

        return $this->backToDashboard($dailyItem)
            ->with('status', "{$name} removed from today's menu.");
    }

    /** Back to the dashboard, on the tab the item lives in. */
    private function backToDashboard(DailyItem $dailyItem): RedirectResponse
    {
        return redirect()->to(route('dashboard') . '#tab-' . $dailyItem->item->category_id);
    }
}
