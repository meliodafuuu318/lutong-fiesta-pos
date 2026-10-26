<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboard): View
    {
        $dailySales = $dashboard->dailySales();
        $weeklySales = $dashboard->weeklySales();
        $week = $dashboard->weekBreakdown();
        $pendingOrders = $dashboard->pendingOrders();

        $pendingDeliveryCount = $pendingOrders
            ->filter(fn (Transaction $order) => $order->delivery?->status === 'PENDING')
            ->count();
        $pendingPaymentCount = $pendingOrders
            ->filter(fn (Transaction $order) => $order->payment_status === 'PENDING')
            ->count();

        return view('dashboard', compact(
            'dailySales',
            'weeklySales',
            'week',
            'pendingOrders',
            'pendingDeliveryCount',
            'pendingPaymentCount',
        ));
    }
}
