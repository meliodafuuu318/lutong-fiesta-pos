<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
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
        return view('transactions.create');
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
