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

        if ($request->has('date_from')) {
            $expenses->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $expenses->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        $total = $expenses->sum('amount');
        $todayTotal = Expense::today()->sum('amount');
        $weeklyTotal = Expense::weekly()->sum('amount');

        return $this->success('Expense data retrieved successfully.', [
            'expenses' => $expenses->get(),
            'total' => $total,
            'today_total' => $todayTotal,
            'weekly_total' => $weeklyTotal
        ], 200);
    }

    public function store(StoreExpenseRequest $request) {
        $expense = Expense::create($request->validated());

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
