<?php

namespace App\Services;

use App\Models\TransactionPayment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * The numbers behind the payments page: money that came in, by payment method and by day.
 *
 * - A payment belongs to the day it was recorded (transaction_payments.created_at), not the day of
 *   its order, so paying an old order today counts as today's money.
 * - "Received" is the amount paid minus the change given back, because only that stays in the
 *   till. A payment that covered more than the balance leaves a negative outstanding_balance, and
 *   that negative part is the change (the same rule as TransactionPayment::changeGiven()).
 * - Payments on cancelled and refunded orders are left out unless asked for, the same way the
 *   dashboard treats those orders as void.
 *
 * Filters are a plain array: date_from, date_to (Y-m-d or null), method, type, search (all
 * nullable) and scope (one of SCOPES).
 */
class PaymentReportService
{
    public const SCOPES = ['active', 'void', 'all'];

    public const SORTS = ['created_at', 'payment_amount', 'received', 'payment_method', 'reference_number'];

    private const VOID_STATUSES = ['CANCELLED', 'REFUNDED'];

    /** Same rules as TransactionPayment::changeGiven() and amountReceived(), for use in SQL. */
    private const CHANGE_SQL = 'CASE WHEN transaction_payments.outstanding_balance < 0 THEN -transaction_payments.outstanding_balance ELSE 0 END';

    private const RECEIVED_SQL = 'transaction_payments.payment_amount - (' . self::CHANGE_SQL . ')';

    /**
     * Totals for each payment method (all of them, zeros included) and overall.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     methods: Collection<string, array{method: string, payments: int, paid: float, change: float, received: float}>,
     *     total: array{payments: int, paid: float, change: float, received: float}
     * }
     */
    public function summary(array $filters): array
    {
        $found = $this->base($filters)
            ->groupBy('transaction_payments.payment_method')
            ->selectRaw('transaction_payments.payment_method as method')
            ->selectRaw('COUNT(*) as payments_count')
            ->selectRaw('SUM(transaction_payments.payment_amount) as amount_paid')
            ->selectRaw('SUM(' . self::CHANGE_SQL . ') as change_given')
            ->selectRaw('SUM(' . self::RECEIVED_SQL . ') as amount_received')
            ->get()
            ->keyBy('method');

        $methods = collect(TransactionPayment::METHODS)->mapWithKeys(fn (string $method) => [
            $method => [
                'method' => $method,
                'payments' => (int) ($found[$method]->payments_count ?? 0),
                'paid' => round((float) ($found[$method]->amount_paid ?? 0), 2),
                'change' => round((float) ($found[$method]->change_given ?? 0), 2),
                'received' => round((float) ($found[$method]->amount_received ?? 0), 2),
            ],
        ]);

        return [
            'methods' => $methods,
            'total' => [
                'payments' => $methods->sum('payments'),
                'paid' => round($methods->sum('paid'), 2),
                'change' => round($methods->sum('change'), 2),
                'received' => round($methods->sum('received'), 2),
            ],
        ];
    }

    /**
     * What came in on each day, split by method; newest day first. Only days with payments appear.
     *
     * @param  array<string, mixed>  $filters
     * @return array{days: Collection<int, array{day: CarbonImmutable, methods: array<string, float>, received: float}>, truncated: bool}
     */
    public function byDay(array $filters, int $limit = 31): array
    {
        $days = $this->base($filters)
            ->groupByRaw('DATE(transaction_payments.created_at), transaction_payments.payment_method')
            ->selectRaw('DATE(transaction_payments.created_at) as day')
            ->selectRaw('transaction_payments.payment_method as method')
            ->selectRaw('SUM(' . self::RECEIVED_SQL . ') as amount_received')
            ->get()
            ->groupBy('day')
            ->map(function (Collection $rows, string $day) {
                $methods = collect(TransactionPayment::METHODS)->mapWithKeys(
                    fn (string $method) => [$method => round((float) ($rows->firstWhere('method', $method)->amount_received ?? 0), 2)]
                );

                return [
                    'day' => CarbonImmutable::parse($day)->startOfDay(),
                    'methods' => $methods->all(),
                    'received' => round($methods->sum(), 2),
                ];
            })
            ->sortByDesc(fn (array $row) => $row['day']->timestamp)
            ->values();

        return ['days' => $days->take($limit), 'truncated' => $days->count() > $limit];
    }

    /**
     * What the "active" scope leaves out: payments on cancelled or refunded orders, with the other
     * filters still applied.
     *
     * @param  array<string, mixed>  $filters
     * @return array{payments: int, received: float}
     */
    public function excluded(array $filters): array
    {
        $row = $this->base(['scope' => 'void'] + $filters)
            ->selectRaw('COUNT(*) as payments_count')
            ->selectRaw('SUM(' . self::RECEIVED_SQL . ') as amount_received')
            ->first();

        return ['payments' => (int) ($row->payments_count ?? 0), 'received' => round((float) ($row->amount_received ?? 0), 2)];
    }

    /**
     * One page of payments. Each row also carries reference_number, transaction_type and transaction_status.
     *
     * @param  array<string, mixed>  $filters
     */
    public function payments(array $filters, string $sortBy, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = $this->base($filters)
            ->select('transaction_payments.*')
            ->addSelect('transactions.reference_number', 'transactions.type as transaction_type', 'transactions.status as transaction_status');

        $direction = $direction === 'asc' ? 'asc' : 'desc';

        match ($sortBy) {
            'received' => $query->orderByRaw(self::RECEIVED_SQL . ' ' . $direction),
            'reference_number' => $query->orderBy('transactions.reference_number', $direction),
            'payment_amount', 'payment_method' => $query->orderBy('transaction_payments.' . $sortBy, $direction),
            default => $query->orderBy('transaction_payments.created_at', $direction),
        };

        return $query->orderByDesc('transaction_payments.id')->paginate($perPage)->withQueryString();
    }

    /**
     * Payments joined to their transaction, with the filters applied.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<TransactionPayment>
     */
    private function base(array $filters): Builder
    {
        return TransactionPayment::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date) => $q->where('transaction_payments.created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $date) => $q->where('transaction_payments.created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->when($filters['method'] ?? null, fn (Builder $q, string $method) => $q->where('transaction_payments.payment_method', $method))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('transactions.type', $type))
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where('transactions.reference_number', 'like', '%' . $search . '%'))
            ->when(
                $filters['scope'] ?? 'active',
                fn (Builder $q, string $scope) => match ($scope) {
                    'void' => $q->whereIn('transactions.status', self::VOID_STATUSES),
                    'all' => $q,
                    default => $q->whereNotIn('transactions.status', self::VOID_STATUSES),
                },
            );
    }
}
