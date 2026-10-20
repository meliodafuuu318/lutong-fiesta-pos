<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\View\View;

class ItemController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): View
    {
        $categories = Category::orderBy('id')->get();

        // One paginator per category, each with its own page parameter so that
        // paging through one tab leaves the others where they are.
        $itemsByCategory = $categories->mapWithKeys(fn (Category $category) => [
            $category->id => Item::where('category_id', $category->id)
                ->orderBy('name')
                ->paginate(self::PER_PAGE, ['*'], "category_{$category->id}_page")
                ->withQueryString()
                ->fragment("tab-{$category->id}"),
        ]);

        return view('items.index', compact('categories', 'itemsByCategory'));
    }
}
