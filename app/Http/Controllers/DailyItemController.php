<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Item,
    DailyItem
};
use App\Http\Requests\DailyItem\{
    AddDailyItemRequest,
    UpdateDailyItemRequest
};

class DailyItemController extends Controller
{
    public function index() {
        $dailyMenu = DailyItem::todayMenu()->get();
        return $this->success('Today\'s menu retrieved successfully', $dailyMenu->load('item'), 200);
    }

    public function addDailyItem(AddDailyItemRequest $request) {
        if (DailyItem::where('item_id', $request->input('item_id'))->whereDate('created_at', today())->exists()) {
            return $this->error('Item already exists in the daily menu', 400);
        }

        $dailyItem = DailyItem::create([
            'item_id' => $request->input('item_id'),
            'price' => $request->input('price'),
            'date' => today(),
            'status' => 'AVAILABLE'
        ]);

        return $this->success('Daily item added successfully', $dailyItem->load('item'), 201);
    }

    public function removeDailyItem(DailyItem $dailyItem) {
        if ($dailyItem->transactionItems()->exists()) {
            return $this->error('Cannot remove daily item that has associated transaction items', 400);
        }

        if ($dailyItem->date != today()) {
            return $this->error('Cannot remove daily item that is not from today\'s menu', 400);
        }

        $dailyItem->delete();

        return $this->success('Daily item removed successfully', null, 200);
    }

    public function updateDailyItem(UpdateDailyItemRequest $request, DailyItem $dailyItem) {
        $dailyItem->update($request->validated());
        return $this->success('Daily item updated successfully', $dailyItem->load('item'), 200);
    }
}
