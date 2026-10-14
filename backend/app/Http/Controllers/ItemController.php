<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Item\{
    IndexItemRequest,
    StoreItemRequest,
    UpdateItemRequest
};
use App\Models\Item;
use App\Http\Resources\ItemResource;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(IndexItemRequest $request)
    {
        // filters
        $search = $request->input('search');
        $categoryFilter = $request->input('category', 'ALL');
        $statusFilter = $request->input('status', 'ALL');
        
        // pagination and sorting
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = Item::query();

        // Apply search filter
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply category filter
        if ($categoryFilter !== 'ALL') {
            $query->whereHas('category', function ($q) use ($categoryFilter) {
                $q->where('name', $categoryFilter);
            });
        }

        // Apply status filter
        if ($statusFilter !== 'ALL') {
            $isActive = $statusFilter === 'ACTIVE' ? true : false;
            $query->where('is_active', $isActive);
        }

        // Apply sorting and paginations
        $query->orderBy($sortBy, $sortOrder);
        $items = $query->paginate($perPage);

        $paginationData = $this->pagePaginationData($items);

        return $this->success('Items retrieved successfully', [
            'items' => ItemResource::collection($items),
            'pagination' => $paginationData
        ], 200);
    }

    public function store(StoreItemRequest $request)
    {
        DB::beginTransaction();

        try {
            $item = Item::create([
                'name' => $request->input('name'),
                'default_price' => $request->input('default_price'),
                'category_id' => $request->input('category_id'),
                'is_active' => true
            ]);
            DB::commit();
            return $this->success('Item created successfully', new ItemResource($item), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create item: ' . $e->getMessage(), 500);
        }
    }

    public function show(Item $item)
    {
        return $this->success('Item retrieved successfully', new ItemResource($item), 200);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        DB::beginTransaction();

        try {
            $item->update($request->only(['name', 'default_price', 'category_id', 'is_active']));
            DB::commit();
            return $this->success('Item updated successfully', new ItemResource($item), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update item: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Item $item)
    {
        DB::beginTransaction();

        try {
            $item->delete();
            DB::commit();
            return $this->success('Item deleted successfully', null, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to delete item: ' . $e->getMessage(), 500);
        }
    }
}
