@extends('layouts.main')

@section('title', 'Payments · ' . config('app.name'))

@section('main')
@php
    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);
    $methodLabels = ['CASH' => 'Cash', 'GCASH' => 'GCash', 'BANK TRANSFER' => 'Bank Transfer'];
    $input = 'mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500';
    $label = 'block text-xs font-bold uppercase tracking-wide text-ink-soft';

    // Below xl each row of the table becomes a card of label/value rows (same as the transactions list).
    $cardRow = 'max-xl:flex max-xl:items-center max-xl:justify-between max-xl:gap-3 max-xl:border-t max-xl:border-cream-200 max-xl:px-0 max-xl:py-2 max-xl:before:text-xs max-xl:before:font-bold max-xl:before:uppercase max-xl:before:tracking-wide max-xl:before:text-ink-soft max-xl:before:content-[attr(data-label)]';

    $rangeLabel = match (true) {
        ! $from && ! $to => 'All dates',
        ! $from => 'Up to ' . $to->format('M j, Y'),
        ! $to => 'From ' . $from->format('M j, Y'),
        $from->isSameDay($to) => $from->format('l, M j, Y'),
        default => $from->format('M j, Y') . ' – ' . $to->format('M j, Y'),
    };

    $presetUrl = fn ($start, $end) => request()->fullUrlWithQuery([
        'date_from' => $start->toDateString(),
        'date_to' => $end->toDateString(),
        'page' => null,
    ]);

    $total = $summary['total'];
@endphp

<div class="w-full">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Payments</h1>
    </div>

    <form method="GET" action="{{ route('payments.index') }}" class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50 p-4">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
            <div class="col-span-2">
                <label for="payments-search" class="{{ $label }}">Search</label>
                <input id="payments-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search reference number..." class="{{ $input }}">
            </div>
            <div>
                <label for="payments-method" class="{{ $label }}">Payment Method</label>
                <select id="payments-method" name="method" class="{{ $input }} cursor-pointer">
                    <option value="" @selected(empty($filters['method']))>All</option>
                    @foreach ($methodLabels as $value => $text)
                        <option value="{{ $value }}" @selected($filters['method'] === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payments-type" class="{{ $label }}">Type</label>
                <select id="payments-type" name="type" class="{{ $input }} cursor-pointer">
                    <option value="" @selected(empty($filters['type']))>All</option>
                    <option value="WALK IN" @selected($filters['type'] === 'WALK IN')>Walk In</option>
                    <option value="DELIVERY" @selected($filters['type'] === 'DELIVERY')>Delivery</option>
                </select>
            </div>
            <div class="col-span-2 min-[26rem]:col-span-1">
                <label for="payments-date-from" class="{{ $label }}">Payment Date From</label>
                <input id="payments-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="{{ $input }}">
            </div>
            <div class="col-span-2 min-[26rem]:col-span-1">
                <label for="payments-date-to" class="{{ $label }}">Payment Date To</label>
                <input id="payments-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="{{ $input }}">
            </div>
            <div>
                <label for="payments-scope" class="{{ $label }}">Orders</label>
                <select id="payments-scope" name="scope" class="{{ $input }} cursor-pointer">
                    <option value="active" @selected($filters['scope'] === 'active')>Active</option>
                    <option value="void" @selected($filters['scope'] === 'void')>Cancelled / Returned</option>
                    <option value="all" @selected($filters['scope'] === 'all')>All</option>
                </select>
            </div>
            <div>
                <label for="payments-sort-by" class="{{ $label }}">Sort By</label>
                <select id="payments-sort-by" name="sort_by" class="{{ $input }} cursor-pointer">
                    <option value="created_at" @selected($sortBy === 'created_at')>Payment Date</option>
                    <option value="received" @selected($sortBy === 'received')>Received</option>
                    <option value="payment_amount" @selected($sortBy === 'payment_amount')>Amount Paid</option>
                    <option value="payment_method" @selected($sortBy === 'payment_method')>Method</option>
                    <option value="reference_number" @selected($sortBy === 'reference_number')>Reference</option>
                </select>
            </div>
            <div>
                <label for="payments-sort-order" class="{{ $label }}">Order</label>
                <select id="payments-sort-order" name="sort_order" class="{{ $input }} cursor-pointer">
                    <option value="desc" @selected($sortOrder === 'desc')>Descending</option>
                    <option value="asc" @selected($sortOrder === 'asc')>Ascending</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="{{ $label }}">Quick range</span>
                @foreach ($presets as $text => [$start, $end])
                    @php($isCurrent = $from && $to && $from->isSameDay($start) && $to->isSameDay($end))
                    <a
                        href="{{ $presetUrl($start, $end) }}"
                        @class([
                            'cursor-pointer rounded-lg border-2 px-3 py-1.5 text-center text-sm font-bold',
                            'border-teal-700 bg-teal-700 text-cream-50' => $isCurrent,
                            'border-cream-200 bg-white hover:border-teal-500' => ! $isCurrent,
                        ])
                        @if ($isCurrent) aria-current="true" @endif
                    >{{ $text }}</a>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-2 w-full sm:w-auto sm:flex sm:items-center">
                <a href="{{ route('payments.index') }}" class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 text-center text-sm font-bold hover:border-teal-500 sm:py-2">Reset</a>
                <button type="submit" class="cursor-pointer rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-bold text-cream-50 hover:bg-teal-600 sm:py-2">Apply</button>
            </div>
        </div>
    </form>

    {{-- ============ What came in ============ --}}
    <section class="mt-6" aria-labelledby="payments-summary-title">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4">
            <h2 id="payments-summary-title" class="font-display text-xl font-bold text-teal-900">{{ $rangeLabel }}</h2>
            <p class="text-sm font-semibold text-ink-soft">{{ number_format($total['payments']) }} {{ \Illuminate\Support\Str::plural('payment', $total['payments']) }}</p>
        </div>

        <dl class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($summary['methods'] as $method => $row)
                <div class="rounded-xl border-2 border-t-4 border-cream-200 border-t-teal-600 bg-cream-50 p-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">{{ $methodLabels[$method] ?? $method }}</dt>
                    <dd class="mt-0.5 break-words font-display text-xl font-bold text-teal-900">{{ $peso($row['received']) }}</dd>
                    <dd class="mt-0.5 text-xs font-semibold text-ink-soft">
                        {{ number_format($row['payments']) }} {{ \Illuminate\Support\Str::plural('payment', $row['payments']) }}
                        @if ($row['change'] > 0)
                            <span class="block">{{ $peso($row['paid']) }} paid, {{ $peso($row['change']) }} change</span>
                        @endif
                    </dd>
                </div>
            @endforeach
            <div class="col-span-2 rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-4 lg:col-span-1">
                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Total Received</dt>
                <dd class="mt-0.5 break-words font-display text-xl font-bold text-teal-900">{{ $peso($total['received']) }}</dd>
                <dd class="mt-0.5 text-xs font-semibold text-ink-soft">
                    {{ number_format($total['payments']) }} {{ \Illuminate\Support\Str::plural('payment', $total['payments']) }}
                    @if ($total['change'] > 0)
                        <span class="block">{{ $peso($total['paid']) }} paid, {{ $peso($total['change']) }} change</span>
                    @endif
                </dd>
            </div>
        </dl>

        <p class="mt-2 text-xs font-semibold text-ink-soft">
            Received is the amount paid minus any change given back. A payment counts on the day it was recorded, even if the order was made earlier.
            @if ($excluded && $excluded['payments'] > 0)
                Not counted: {{ $peso($excluded['received']) }} from {{ number_format($excluded['payments']) }} {{ \Illuminate\Support\Str::plural('payment', $excluded['payments']) }} on cancelled or returned orders (choose Cancelled / Returned or All under Orders to see them).
            @endif
        </p>
    </section>

    {{-- ============ Day by day (only when the dates cover more than one day) ============ --}}
    @if ($days->count() > 1)
        <section class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50" aria-labelledby="payments-days-title">
            <div class="border-b-2 border-cream-200 px-5 py-3">
                <h2 id="payments-days-title" class="font-display text-xl font-bold text-teal-900">Day by day</h2>
                @if ($daysTruncated)
                    <p class="mt-0.5 text-sm font-semibold text-ink-soft">Showing the latest {{ $days->count() }} days. Narrow the dates to see earlier ones.</p>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[32rem] table-auto text-right">
                    <caption class="sr-only">Amount received per day, by payment method</caption>
                    <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left">Day</th>
                            @foreach ($methodLabels as $text)
                                <th scope="col" class="px-4 py-3">{{ $text }}</th>
                            @endforeach
                            <th scope="col" class="px-4 py-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $day)
                            <tr class="border-t-2 border-cream-200">
                                <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-semibold text-ink">{{ $day['day']->format('D, M j, Y') }}</th>
                                @foreach ($day['methods'] as $amount)
                                    <td @class(['whitespace-nowrap px-4 py-3 font-semibold', 'text-ink' => $amount > 0, 'text-ink-soft' => $amount <= 0])>{{ $peso($amount) }}</td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-teal-700">{{ $peso($day['received']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- ============ Every payment ============ --}}
    @if ($payments->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            No payments found.
        </p>
    @else
        <div class="mt-6 overflow-x-auto rounded-xl border-2 border-cream-200 bg-cream-50 max-xl:overflow-visible max-xl:border-0 max-xl:bg-transparent">
            <table class="w-full table-auto text-center max-xl:block">
                <caption class="sr-only">Payments</caption>
                <thead class="bg-cream-100 max-xl:hidden text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                    <tr>
                        <th scope="col" class="px-4 py-3">Reference Number</th>
                        <th scope="col" class="px-4 py-3">Type</th>
                        <th scope="col" class="px-4 py-3">Payment Method</th>
                        <th scope="col" class="px-4 py-3">Amount Paid</th>
                        <th scope="col" class="px-4 py-3">Change</th>
                        <th scope="col" class="px-4 py-3">Received</th>
                        <th scope="col" class="px-4 py-3">Order Status</th>
                        <th scope="col" class="px-4 py-3">Payment Date</th>
                    </tr>
                </thead>
                <tbody class="max-xl:grid max-xl:grid-cols-1 max-xl:gap-3 sm:max-xl:grid-cols-2">
                    @foreach ($payments as $payment)
                        <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60 cursor-pointer max-xl:block max-xl:rounded-xl max-xl:border-2 max-xl:bg-cream-50 max-xl:p-4" onclick="window.location='{{ route('transactions.show', ['transaction' => $payment->transaction_id, 'from' => request()->fullUrl()]) }}'">
                            <th scope="row" class="whitespace-nowrap px-4 py-3 font-mono text-sm font-bold text-ink max-xl:block max-xl:p-0 max-xl:pb-2 max-xl:text-left">{{ $payment->reference_number }}</th>
                            <td data-label="Type" class="whitespace-nowrap px-4 py-3 font-semibold text-ink {{ $cardRow }}">{{ \Illuminate\Support\Str::title(strtolower($payment->transaction_type)) }}</td>
                            <td data-label="Payment Method" class="whitespace-nowrap px-4 py-3 font-semibold text-ink {{ $cardRow }}">{{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td data-label="Amount Paid" class="whitespace-nowrap px-4 py-3 font-semibold text-ink {{ $cardRow }}">{{ $peso($payment->payment_amount) }}</td>
                            <td data-label="Change" @class(['whitespace-nowrap px-4 py-3 font-semibold', $cardRow, 'text-ink' => $payment->changeGiven() > 0, 'text-ink-soft' => $payment->changeGiven() <= 0])>{{ $peso($payment->changeGiven()) }}</td>
                            <td data-label="Received" class="whitespace-nowrap px-4 py-3 font-bold text-teal-700 {{ $cardRow }}">{{ $peso($payment->amountReceived()) }}</td>
                            <td data-label="Order Status" class="px-4 py-3 {{ $cardRow }}"><x-status-badge :status="$payment->transaction_status" /></td>
                            <td data-label="Date" class="whitespace-nowrap px-4 py-3 font-semibold text-ink {{ $cardRow }} max-xl:text-right">
                                {{ $payment->created_at->format('M j, Y') }}
                                <span class="block text-xs font-semibold text-ink-soft">{{ $payment->created_at->format('g:i A') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payments->links('pagination.lutong') }}
        </div>
    @endif
</div>
@endsection
