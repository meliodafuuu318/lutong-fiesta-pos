<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Item,
    DailyItem
};
use App\Http\Requests\DailyItem\{
    AddDailyItemRequest,
    UpdateDailyItemRequest,
    IndexDailyItemRequest
};
use App\Http\Resources\DailyItemResource;

class DailyItemController extends Controller
{
    public function index(IndexDailyItemRequest $request) {
        $dailyMenu = DailyItem::todayMenu();

        if ($request->has('category')) {
            $category = $request->input('category');
            $dailyMenu->whereHas('item.category', function ($query) use ($category) {
                $query->where('name', $category);
            });
        }

        $dailyMenu = $dailyMenu->get();

        return $this->success('Today\'s menu retrieved successfully', DailyItemResource::collection($dailyMenu), 200);
    }

    public function addDailyItem(AddDailyItemRequest $request) {
        if (DailyItem::todayMenu()->where('item_id', $request->input('item_id'))->exists()) {
            return $this->error('Item already exists in the daily menu', 400);
        }

        $dailyItem = DailyItem::create([
            'item_id' => $request->input('item_id'),
            'price' => $request->input('price'),
            'date' => today(),
            'status' => 'AVAILABLE'
        ]);

        return $this->success('Daily item added successfully', new DailyItemResource($dailyItem), 201);
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
        if ($dailyItem->date != today()) {
            return $this->error('Cannot update daily item that is not from today\'s menu', 400);
        }

        $dailyItem->update($request->validated());

        return $this->success('Daily item updated successfully', new DailyItemResource($dailyItem), 200);
    }

    public function tempGetAllDailyItems() {
        $dailyItems = DailyItem::with('item.category')->get();
        return $this->success('All daily items retrieved successfully', DailyItemResource::collection($dailyItems), 200);
    }
}
