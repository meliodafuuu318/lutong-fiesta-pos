<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TransactionPayment;
use App\Services\PaymentReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * Every payment taken, with what came in per payment method and per day.
     *
     * Opening the page without any dates shows today, which is what a cashier usually wants; the
     * form always sends both date fields, so clearing them (or Reset, then clearing) means "all".
     * A value that doesn't make sense falls back to its default instead of showing an error.
     */
    public function index(Request $request, PaymentReportService $payments): View
    {
        $today = CarbonImmutable::today();

        if ($request->has('date_from') || $request->has('date_to')) {
            $from = $this->date($request->query('date_from'));
            $to = $this->date($request->query('date_to'));
        } else {
            $from = $to = $today;
        }

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $method = $request->query('method');
        $type = $request->query('type');
        $scope = $request->query('scope');
        $search = trim((string) $request->query('search', ''));
        $sortBy = $request->query('sort_by');

        $filters = [
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'method' => is_string($method) && in_array($method, TransactionPayment::METHODS, true) ? $method : null,
            'type' => is_string($type) && in_array($type, ['WALK IN', 'DELIVERY'], true) ? $type : null,
            'search' => $search !== '' ? mb_substr($search, 0, 50) : null,
            'scope' => is_string($scope) && in_array($scope, PaymentReportService::SCOPES, true) ? $scope : 'active',
        ];

        $sortBy = is_string($sortBy) && in_array($sortBy, PaymentReportService::SORTS, true) ? $sortBy : 'created_at';
        $sortOrder = $request->query('sort_order') === 'asc' ? 'asc' : 'desc';

        $summary = $payments->summary($filters);
        $byDay = $payments->byDay($filters);

        return view('payments.index', [
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'summary' => $summary,
            'days' => $byDay['days'],
            'daysTruncated' => $byDay['truncated'],
            'excluded' => $filters['scope'] === 'active' ? $payments->excluded($filters) : null,
            'payments' => $payments->payments($filters, $sortBy, $sortOrder, self::PER_PAGE),
            'presets' => [
                'Today' => [$today, $today],
                'Yesterday' => [$today->subDay(), $today->subDay()],
                'This week' => [$today->startOfWeek(), $today->endOfWeek()->startOfDay()],
                'This month' => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
            ],
        ]);
    }

    /** A Y-m-d date, or null for anything else (including dates that don't exist, like Feb 31). */
    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
