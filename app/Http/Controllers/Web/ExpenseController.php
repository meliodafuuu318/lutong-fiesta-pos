<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\WebStoreExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View|RedirectResponse
    {
        $query = Expense::query();

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        // Covers the whole filtered range, not just the current page.
        $total = $query->sum('amount');

        $expenses = $query
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Deleting the last expense on the last page leaves that page empty: go back one.
        if ($expenses->isEmpty() && $expenses->currentPage() > 1) {
            $request->session()->reflash();

            return redirect()->to($expenses->url($expenses->lastPage()));
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return view('expenses.index', compact('expenses', 'total', 'dateFrom', 'dateTo'));
    }

    public function store(WebStoreExpenseRequest $request): RedirectResponse
    {
        // Expenses are always recorded for today.
        $expense = Expense::create([...$request->validated(), 'expense_date' => today()->toDateString()]);

        return redirect()->route('expenses.index')
            ->with('status', 'Expense of ₱' . number_format((float) $expense->amount, 2) . ' added.');
    }

    public function update(WebStoreExpenseRequest $request, Expense $expense): RedirectResponse
    {
        // Only the amount and description can change; the date stays as recorded.
        $expense->update($request->validated());

        // Stay on the same page of the list.
        return redirect()->to(url()->previous(route('expenses.index')))
            ->with('status', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->to(url()->previous(route('expenses.index')))
            ->with('status', 'Expense deleted.');
    }
}
