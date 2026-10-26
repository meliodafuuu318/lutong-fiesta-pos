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

        return $this->success('Dashboard data retrieved successfully.', [
            'daily_sales' => $dailySales,
            'weekly_sales' => $weeklySales,
            'pending_deliveries' => $pendingDeliveries,
            'pending_payments' => $pendingPayments,
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
