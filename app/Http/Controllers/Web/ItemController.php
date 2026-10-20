<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\WebStoreItemRequest;
use App\Http\Requests\Item\WebToggleItemActiveRequest;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItemController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): View
    {
        $categories = Category::orderBy('id')->get();

        // One paginator per category, each with its own page parameter so that
        // paging through one tab leaves the others where they are.
        $itemsByCategory = $categories->mapWithKeys(function (Category $category) {
            $pageName = "category_{$category->id}_page";
            $query = Item::where('category_id', $category->id)->orderBy('name');

            $items = $query->paginate(self::PER_PAGE, ['*'], $pageName);

            // Deleted the last item of the last page? Show the new last page instead of an empty one.
            if ($items->isEmpty() && $items->total() > 0) {
                $items = $query->paginate(self::PER_PAGE, ['*'], $pageName, $items->lastPage());
            }

            return [$category->id => $items->withQueryString()->fragment("tab-{$category->id}")];
        });

        return view('items.index', compact('categories', 'itemsByCategory'));
    }

    public function store(WebStoreItemRequest $request): RedirectResponse
    {
        $item = Item::create([...$request->validated(), 'is_active' => true]);

        return redirect()->to(route('items.index') . '#tab-' . $item->category_id)
            ->with('status', "{$item->name} added.");
    }

    public function toggleActive(WebToggleItemActiveRequest $request, Item $item): JsonResponse
    {
        $item->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['is_active' => $item->is_active]);
    }

    public function destroy(Item $item): RedirectResponse
    {
        // Deleting an item cascades to its menu entries and, from there, to the
        // order lines that reference them. Don't wipe sales history.
        if ($item->hasTransactionHistory()) {
            return $this->backToItems($item)->withErrors([
                'item' => "{$item->name} has order history, so it can't be deleted. Mark it inactive instead.",
            ]);
        }

        $name = $item->name;
        $response = $this->backToItems($item);

        $item->delete();

        return $response->with('status', "{$name} deleted.");
    }

    /** Back to the page the request came from, on the tab the item lives in. */
    private function backToItems(Item $item): RedirectResponse
    {
        return redirect()->to(url()->previous(route('items.index')) . '#tab-' . $item->category_id);
    }
}
