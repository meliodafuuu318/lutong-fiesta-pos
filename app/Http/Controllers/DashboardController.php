<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index() {
        $dailySales = $this->getDailySales();
        $weeklySales = $this->getWeeklySales();
        $pendingDeliveries = $this->getPendingDeliveries();
        $pendingPayments = $this->getPendingPayments();
        $dailyExpenses = $this->getDailyExpenses();
        $weeklyExpenses = $this->getWeeklyExpenses();

        return $this->success('Dashboard data retrieved successfully.', [
            'daily_sales' => $dailySales,
            'weekly_sales' => $weeklySales,
            'pending_deliveries' => $pendingDeliveries,
            'pending_payments' => $pendingPayments,
            'daily_expenses' => $dailyExpenses,
            'weekly_expenses' => $weeklyExpenses,
        ], 200);
    }

    private function getDailySales() {
        return [
            'sales' => Transaction::today()->sum('total'),
            'payments' => Transaction::today()->where('payment_status', 'PAID')->sum('total'),
        ];
    }

    public function getWeeklySales() {
        return [
            'sales' => Transaction::weekly()->sum('total'),
            'payments' => Transaction::weekly()->where('payment_status', 'PAID')->sum('total')
        ];
    }

    private function getDailyExpenses() {
        return [
            'expenses' => Expense::today()->sum('amount'),
        ];
    }

    private function getWeeklyExpenses() {
        return [
            'expenses' => Expense::weekly()->sum('amount'),
        ];
    }

    private function getPendingDeliveries() {
        return Transaction::pendingDelivery()
            ->with('delivery')
            ->get();
    }

    private function getPendingPayments() {
        return Transaction::pendingPayment()
            ->withSum('transactionPayments', 'payment_amount')
            ->get();
    }
}
