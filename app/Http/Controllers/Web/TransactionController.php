<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Exceptions\TransactionException;
use App\Http\Requests\Transaction\WebStoreTransactionRequest;
use App\Models\Category;
use App\Models\DailyItem;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $query = Transaction::with('delivery')
            ->withSum('transactionPayments', 'payment_amount');

        if ($request->filled('search')) {
            $query->where('reference_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['transaction_date', 'total', 'reference_number', 'status', 'payment_status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('transaction_date')->orderByDesc('id');
        }

        if (! in_array($sortBy, $allowedSorts, true) || $sortBy === 'transaction_date') {
            $query->orderByDesc('id');
        }

        $transactions = $query->paginate($request->input('per_page', self::PER_PAGE))->withQueryString();

        $search = $request->input('search');
        $type = $request->input('type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return view('transactions.index', compact('transactions', 'search', 'type', 'dateFrom', 'dateTo', 'sortBy', 'sortOrder'));
    }

    public function create(): View
    {
        // Today's menu, by category. Sold out items are listed too, but can't be picked.
        $menuByCategory = DailyItem::todayMenu()
            ->with('item')
            ->get()
            ->sortBy(fn (DailyItem $dailyItem) => $dailyItem->item->name)
            ->groupBy(fn (DailyItem $dailyItem) => $dailyItem->item->category_id);

        $categories = Category::whereIn('id', $menuByCategory->keys())
            ->orderBy('id')
            ->get();

        return view('transactions.create', compact('categories', 'menuByCategory'));
    }

    public function store(WebStoreTransactionRequest $request, TransactionService $transactions): RedirectResponse
    {
        try {
            $transaction = $transactions->create($request->validated());
        } catch (TransactionException $e) {
            // An item stopped being available between opening the form and submitting it.
            $name = $e->dailyItemId ? DailyItem::with('item')->find($e->dailyItemId)?->item?->name : null;

            return back()->withInput()->withErrors([
                'transaction' => $name
                    ? "{$name} isn't available today. Remove it and try again."
                    : $e->getMessage(),
            ], 'createTransaction');
        }

        return redirect()->route('transactions.show', $transaction)
            ->with('status', "Transaction {$transaction->reference_number} created.");
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load([
            'transactionItems.dailyItem.item',
            'transactionPayments',
            'delivery',
        ]);

        $transaction->loadSum('transactionPayments', 'payment_amount');

        $payments = $transaction->transactionPayments->sortByDesc('id');

        return view('transactions.show', compact('transaction', 'payments'));
    }
}
