<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** The dashboard is different for each role. */
    public function index(Request $request, DashboardService $dashboard, MenuService $menu): View
    {
        return match ($request->user()->role) {
            User::ROLE_CASHIER => $this->cashier($menu),
            User::ROLE_RIDER => $this->rider($dashboard),
            default => $this->admin($dashboard),
        };
    }

    private function admin(DashboardService $dashboard): View
    {
        $dailySales = $dashboard->dailySales();
        $weeklySales = $dashboard->weeklySales();
        $monthlySales = $dashboard->monthlySales();
        $dailyExpenses = $dashboard->dailyExpenses();
        $weeklyExpenses = $dashboard->weeklyExpenses();
        $monthlyExpenses = $dashboard->monthlyExpenses();
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
            'monthlySales',
            'dailyExpenses',
            'weeklyExpenses',
            'monthlyExpenses',
            'week',
            'pendingOrders',
            'pendingDeliveryCount',
            'pendingPaymentCount',
        ));
    }

    /** The cashier takes orders here: today's menu on one side, the receipt on the other. */
    private function cashier(MenuService $menu): View
    {
        ['categories' => $categories, 'menuByCategory' => $menuByCategory] = $menu->today();

        return view('cashier.dashboard', compact('categories', 'menuByCategory'));
    }

    /**
     * The rider sees the pending orders straight away. Only delivery orders: walk in
     * orders aren't something a rider can deliver or collect.
     */
    private function rider(DashboardService $dashboard): View
    {
        $pendingOrders = $dashboard->pendingOrders()
            ->where('type', 'DELIVERY')
            ->values();

        $pendingDeliveryCount = $pendingOrders
            ->filter(fn (Transaction $order) => $order->delivery?->status === 'PENDING')
            ->count();
        $pendingPaymentCount = $pendingOrders
            ->filter(fn (Transaction $order) => $order->payment_status === 'PENDING')
            ->count();

        return view('rider.dashboard', compact('pendingOrders', 'pendingDeliveryCount', 'pendingPaymentCount'));
    }
}
