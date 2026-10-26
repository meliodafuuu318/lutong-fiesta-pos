<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Http\Requests\Expense\{
    IndexExpenseRequest,
    StoreExpenseRequest
};

class ExpenseController extends Controller
{
    public function index(IndexExpenseRequest $request) {
        $expenses = Expense::query();

        if ($request->filled('date_from')) {
            $expenses->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $expenses->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        // The total covers the whole filtered range, not just the current page.
        $total = $expenses->sum('amount');
        $todayTotal = Expense::today()->sum('amount');
        $weeklyTotal = Expense::weekly()->sum('amount');

        $paginated = $expenses
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));

        return $this->success('Expense data retrieved successfully.', [
            'expenses' => $paginated->items(),
            'pagination' => $this->pagePaginationData($paginated),
            'total' => $total,
            'today_total' => $todayTotal,
            'weekly_total' => $weeklyTotal
        ], 200);
    }

    public function store(StoreExpenseRequest $request) {
        // Expenses are always recorded for today; the date isn't something the client sets.
        $expense = Expense::create([...$request->validated(), 'expense_date' => today()->toDateString()]);

        return $this->success('Expense created successfully.', $expense, 201);
    }

    public function destroy(Expense $expense) {
        $expense->delete();

        return $this->success('Expense deleted successfully.', null, 200);
    }

    private function getDailyExpenses() {
        return Expense::today()->sum('amount');
    }

    private function getWeeklyExpenses() {
        return Expense::weekly()->sum('amount');
    }
}
