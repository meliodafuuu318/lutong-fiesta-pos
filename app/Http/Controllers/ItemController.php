<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Item\{
    IndexItemRequest
};
use App\Models\Item;

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
            'items' => $items->items(),
            'pagination' => $paginationData
        ], 200);
    }
}
