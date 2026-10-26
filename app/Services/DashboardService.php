<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * The numbers behind the dashboard. Same figures as the API's dashboard
 * endpoint (daily and weekly sales and expenses, pending deliveries, pending
 * payments), plus monthly figures and a per-day breakdown of the week.
 */
class DashboardService
{
    /** @return array{sales: float|int|string, payments: float|int|string} */
    public function dailySales(): array
    {
        return [
            'sales' => Transaction::today()->sum('total'),
            'payments' => Transaction::today()->where('payment_status', 'PAID')->sum('total'),
        ];
    }

    /** @return array{sales: float|int|string, payments: float|int|string} */
    public function weeklySales(): array
    {
        return [
            'sales' => Transaction::weekly()->sum('total'),
            'payments' => Transaction::weekly()->where('payment_status', 'PAID')->sum('total'),
        ];
    }

    /** @return array{sales: float|int|string, payments: float|int|string} */
    public function monthlySales(): array
    {
        return [
            'sales' => Transaction::monthly()->sum('total'),
            'payments' => Transaction::monthly()->where('payment_status', 'PAID')->sum('total'),
        ];
    }

    public function dailyExpenses(): float
    {
        return (float) Expense::today()->sum('amount');
    }

    public function weeklyExpenses(): float
    {
        return (float) Expense::weekly()->sum('amount');
    }

    public function monthlyExpenses(): float
    {
        return (float) Expense::monthly()->sum('amount');
    }

    /**
     * Monday to Sunday of this week, one row per day, with the same
     * definitions as dailySales()/weeklySales() ("payments" = total of PAID orders).
     *
     * @return Collection<int, array{date: \Carbon\CarbonInterface, orders: int, sales: float, payments: float, expenses: float, is_today: bool}>
     */
    public function weekBreakdown(): Collection
    {
        $rows = Transaction::weekly()
            ->selectRaw('DATE(transaction_date) as day, COUNT(*) as orders, SUM(total) as sales, SUM(CASE WHEN payment_status = ? THEN total ELSE 0 END) as payments', ['PAID'])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $expenses = Expense::weekly()
            ->selectRaw('DATE(expense_date) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(CarbonPeriod::create(now()->startOfWeek(), now()->endOfWeek()))
            ->map(function ($date) use ($rows, $expenses) {
                $row = $rows->get($date->toDateString());

                return [
                    'date' => $date,
                    'orders' => (int) ($row->orders ?? 0),
                    'sales' => (float) ($row->sales ?? 0),
                    'payments' => (float) ($row->payments ?? 0),
                    'expenses' => (float) ($expenses[$date->toDateString()] ?? 0),
                    'is_today' => $date->isToday(),
                ];
            })
            ->values();
    }

    /** Delivery orders whose delivery hasn't happened yet. */
    public function pendingDeliveries(): Collection
    {
        return Transaction::pendingDelivery()
            ->active()
            ->with('delivery')
            ->withSum('transactionPayments', 'payment_amount')
            ->get();
    }

    /** Orders that still owe money. */
    public function pendingPayments(): Collection
    {
        return Transaction::pendingPayment()
            ->active()
            ->with('delivery')
            ->withSum('transactionPayments', 'payment_amount')
            ->get();
    }

    /**
     * Pending deliveries and pending payments as one list, each order once,
     * oldest first (the ones that have waited longest come up top).
     */
    public function pendingOrders(): Collection
    {
        return $this->pendingDeliveries()
            ->concat($this->pendingPayments())
            ->unique('id')
            ->sortBy([['transaction_date', 'asc'], ['id', 'asc']])
            ->values();
    }
}
