@extends('layouts.main')

@section('title', 'Dashboard · ' . config('app.name'))

@section('main')
@php
    $pendingCount = $pendingOrders->count();
    $weekMax = max(1, $week->max(fn ($day) => max($day['sales'], $day['payments'], $day['expenses'])));
    $weekHasData = $week->sum('sales') > 0 || $week->sum('expenses') > 0;
    $tabClasses = 'shrink-0 cursor-pointer rounded-full border-2 border-cream-200 bg-cream-50 px-5 py-2 font-bold text-ink hover:border-teal-500 aria-selected:border-teal-700 aria-selected:bg-teal-700 aria-selected:text-cream-50';
    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);
    $signedPeso = fn ($amount) => ($amount < 0 ? '−' : '') . '₱' . number_format(abs((float) $amount), 2);
    $periods = [
        ['label' => 'Today', 'hint' => now()->format('M j'), 'sales' => $dailySales['sales'], 'collected' => $dailySales['payments'], 'expenses' => $dailyExpenses],
        ['label' => 'This week', 'hint' => now()->startOfWeek()->format('M j') . ' – ' . now()->endOfWeek()->format('M j'), 'sales' => $weeklySales['sales'], 'collected' => $weeklySales['payments'], 'expenses' => $weeklyExpenses],
        ['label' => 'This month', 'hint' => now()->format('F Y'), 'sales' => $monthlySales['sales'], 'collected' => $monthlySales['payments'], 'expenses' => $monthlyExpenses],
    ];
@endphp

<div class="pb-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Dashboard</h1>
        <div class="flex flex-wrap items-center gap-3">
            <p class="font-semibold text-ink-soft">{{ now()->format('l, F j, Y') }}</p>
            <div class="flex w-full gap-3 sm:w-auto">
                <button
                    type="button"
                    data-add-expense
                    class="flex-1 cursor-pointer rounded-lg border-2 border-teal-700 px-4 py-2.5 text-center font-display text-base font-bold leading-tight text-teal-700 transition hover:bg-teal-700 hover:text-cream-50 sm:flex-none"
                >Add Expense</button>
                <a
                    href="{{ route('transactions.create') }}"
                    class="flex-1 cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 text-center font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] sm:flex-none"
                >Create Transaction</a>
            </div>
        </div>
    </div>

    <div data-tabs class="mt-2">
        <div
            role="tablist"
            aria-label="Dashboard sections"
            class="sticky top-16 z-10 -mx-4 flex gap-2 overflow-x-auto bg-cream-100 px-4 py-3 sm:-mx-8 sm:px-8"
        >
            <button type="button" role="tab" id="tab-pending" aria-controls="panel-pending" aria-selected="true" tabindex="0" class="{{ $tabClasses }}">
                Pending Orders
                <span class="ml-1.5 inline-block min-w-6 rounded-full px-1.5 text-center text-sm {{ $pendingCount > 0 ? 'bg-amber-500 text-ink' : 'bg-ink/10 text-ink-soft' }}">{{ $pendingCount }}</span>
            </button>
            <button type="button" role="tab" id="tab-analytics" aria-controls="panel-analytics" aria-selected="false" tabindex="-1" class="{{ $tabClasses }}">Analytics</button>
        </div>

        {{-- ============ Pending orders ============ --}}
        <div role="tabpanel" id="panel-pending" aria-labelledby="tab-pending" tabindex="0" class="mt-2" data-pending>
            @if ($pendingOrders->isEmpty())
                <p class="mt-4 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
                    You're all caught up. There are no pending deliveries or payments.
                </p>
            @else
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter pending orders">
                    @foreach ([
                        ['all', 'All', $pendingCount],
                        ['delivery', 'Deliveries', $pendingDeliveryCount],
                        ['payment', 'Payments', $pendingPaymentCount],
                    ] as [$key, $label, $count])
                        <button
                            type="button"
                            data-pending-filter="{{ $key }}"
                            aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}"
                            class="cursor-pointer rounded-full border-2 border-cream-200 bg-cream-50 px-4 py-1.5 text-sm font-bold text-ink hover:border-teal-500 aria-pressed:border-ink aria-pressed:bg-ink aria-pressed:text-cream-50"
                        >{{ $label }} <span class="opacity-70">{{ $count }}</span></button>
                    @endforeach
                </div>

                <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
                    @foreach ($pendingOrders as $order)
                        @php
                            $deliveryPending = $order->delivery?->status === 'PENDING';
                            $paymentPending = $order->payment_status === 'PENDING';
                            $paid = $order->amountPaid();
                            $due = $order->outstandingBalance();
                        @endphp
                        <li
                            data-pending-card
                            data-href="{{ route('transactions.show', $order) }}"
                            data-delivery="{{ $deliveryPending ? '1' : '0' }}"
                            data-payment="{{ $paymentPending ? '1' : '0' }}"
                            class="flex cursor-pointer flex-col rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-4 hover:bg-cream-100/60"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('transactions.show', $order) }}" class="block truncate font-mono text-sm font-bold text-ink underline decoration-cream-200 underline-offset-4 hover:decoration-teal-500">{{ $order->reference_number }}</a>
                                    <p class="mt-0.5 text-xs font-semibold text-ink-soft">
                                        {{ \Illuminate\Support\Str::title(strtolower($order->type)) }} · {{ $order->transaction_date->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-wrap justify-end gap-1">
                                    @if ($deliveryPending)
                                        <span class="whitespace-nowrap rounded-full bg-amber-400/30 px-2.5 py-1 text-xs font-bold text-ink">Delivery</span>
                                    @endif
                                    @if ($paymentPending)
                                        <span class="whitespace-nowrap rounded-full bg-danger/10 px-2.5 py-1 text-xs font-bold text-danger">Payment</span>
                                    @endif
                                </div>
                            </div>

                            @if ($deliveryPending)
                                <p class="mt-3 flex gap-2 text-sm font-semibold text-ink">
                                    <svg class="mt-0.5 size-4 shrink-0 text-ink-soft" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span class="min-w-0 break-words">{{ $order->delivery->address }}</span>
                                </p>
                            @endif

                            <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-dashed border-cream-200 pt-3 text-sm">
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Total</dt>
                                    <dd class="mt-0.5 font-bold text-ink">{{ $peso($order->total) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Paid</dt>
                                    <dd class="mt-0.5 font-bold text-ink">{{ $peso($paid) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Due</dt>
                                    <dd @class(['mt-0.5 font-bold', 'text-danger' => $due > 0, 'text-ink-soft' => $due <= 0])>{{ $peso($due) }}</dd>
                                </div>
                            </dl>

                            <div class="mt-auto grid grid-cols-1 gap-2 pt-4">
                                @if ($paymentPending)
                                    <button
                                        type="button"
                                        data-pending-pay
                                        data-id="{{ $order->id }}"
                                        data-reference="{{ $order->reference_number }}"
                                        data-total="{{ $order->total }}"
                                        data-paid="{{ $paid }}"
                                        data-due="{{ $due }}"
                                        class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
                                    >Add Payment</button>
                                @endif
                                @if ($deliveryPending)
                                    <button
                                        type="button"
                                        data-pending-deliver
                                        data-id="{{ $order->id }}"
                                        data-reference="{{ $order->reference_number }}"
                                        data-address="{{ $order->delivery->address }}"
                                        class="cursor-pointer rounded-lg border-2 border-teal-700 px-4 py-3 font-display text-base font-bold leading-tight text-teal-700 transition hover:bg-teal-700 hover:text-cream-50"
                                    >Mark Delivered</button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p data-pending-filter-empty hidden class="mt-4 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
                    Nothing pending in this filter.
                </p>
            @endif
        </div>

        {{-- ============ Analytics ============ --}}
        <div role="tabpanel" id="panel-analytics" aria-labelledby="tab-analytics" tabindex="0" class="mt-2" hidden>
            <div class="grid grid-cols-1 gap-3 sm:gap-4 lg:grid-cols-3">
                @foreach ($periods as $period)
                    @php($net = (float) $period['collected'] - (float) $period['expenses'])
                    <section class="rounded-xl border-2 border-t-4 border-cream-200 border-t-teal-600 bg-cream-50 p-4" aria-labelledby="period-{{ $loop->index }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <h2 id="period-{{ $loop->index }}" class="font-display text-lg font-bold text-teal-900">{{ $period['label'] }}</h2>
                            <p class="text-xs font-semibold text-ink-soft">{{ $period['hint'] }}</p>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-3">
                            <div class="min-w-0">
                                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Sales</dt>
                                <dd class="mt-0.5 break-words font-display text-lg font-bold text-teal-900 sm:text-xl">{{ $peso($period['sales']) }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Collected</dt>
                                <dd class="mt-0.5 break-words font-display text-lg font-bold text-teal-700 sm:text-xl">{{ $peso($period['collected']) }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Expenses</dt>
                                <dd class="mt-0.5 break-words font-display text-lg font-bold text-danger sm:text-xl">{{ $peso($period['expenses']) }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Net</dt>
                                <dd @class(['mt-0.5 break-words font-display text-lg font-bold sm:text-xl', 'text-teal-900' => $net >= 0, 'text-danger' => $net < 0])>{{ $signedPeso($net) }}</dd>
                            </div>
                        </dl>
                    </section>
                @endforeach
            </div>
            <p class="mt-2 text-xs font-semibold text-ink-soft">Collected is the total of orders marked paid. Net is collected minus expenses.</p>

            <section class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50" aria-labelledby="week-chart-title">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b-2 border-cream-200 px-5 py-3">
                    <h2 id="week-chart-title" class="font-display text-xl font-bold text-teal-900">This week</h2>
                    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold text-ink-soft">
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-amber-500" aria-hidden="true"></span>Sales</li>
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-teal-700" aria-hidden="true"></span>Collected</li>
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-danger" aria-hidden="true"></span>Expenses</li>
                    </ul>
                </div>

                @if ($weekHasData)
                    {{-- The table below has the same numbers for screen readers --}}
                    <div class="px-3 pb-4 pt-6 sm:px-5" aria-hidden="true">
                        <div class="flex h-56 items-end gap-1.5 border-b-2 border-cream-200 sm:gap-3">
                            @foreach ($week as $day)
                                <div class="flex h-full min-w-0 flex-1 items-end justify-center gap-0.5 sm:gap-1" title="{{ $day['date']->format('D, M j') }}: sales {{ $peso($day['sales']) }}, collected {{ $peso($day['payments']) }}, expenses {{ $peso($day['expenses']) }}">
                                    <div class="w-full max-w-7 rounded-t bg-amber-500" style="height: {{ $day['sales'] > 0 ? max(2, round($day['sales'] / $weekMax * 100, 1)) : 0 }}%"></div>
                                    <div class="w-full max-w-7 rounded-t bg-teal-700" style="height: {{ $day['payments'] > 0 ? max(2, round($day['payments'] / $weekMax * 100, 1)) : 0 }}%"></div>
                                    <div class="w-full max-w-7 rounded-t bg-danger" style="height: {{ $day['expenses'] > 0 ? max(2, round($day['expenses'] / $weekMax * 100, 1)) : 0 }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 flex gap-1.5 sm:gap-3">
                            @foreach ($week as $day)
                                <p @class(['min-w-0 flex-1 text-center text-xs font-bold sm:text-sm', 'text-teal-700' => $day['is_today'], 'text-ink-soft' => ! $day['is_today']])>
                                    {{ $day['date']->format('D') }}
                                </p>
                            @endforeach
                        </div>
                        <p class="mt-3 text-right text-xs font-semibold text-ink-soft">Tallest bar: {{ $peso($weekMax) }}</p>
                    </div>
                @else
                    <p class="px-6 py-12 text-center text-ink-soft">No sales or expenses yet this week.</p>
                @endif
            </section>

            <section class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50" aria-labelledby="week-report-title">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 id="week-report-title" class="font-display text-xl font-bold text-teal-900">Daily report</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-right">
                        <caption class="sr-only">Orders, sales, collected payments and expenses for each day of this week</caption>
                        <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left">Day</th>
                                <th scope="col" class="px-4 py-3">Orders</th>
                                <th scope="col" class="px-4 py-3">Sales</th>
                                <th scope="col" class="px-4 py-3">Collected</th>
                                <th scope="col" class="px-4 py-3">Expenses</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($week as $day)
                                <tr @class(['border-t-2 border-cream-200', 'bg-teal-50' => $day['is_today']])>
                                    <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-bold text-ink">
                                        {{ $day['date']->format('D, M j') }}
                                        @if ($day['is_today'])<span class="ml-1 rounded bg-teal-700 px-1.5 py-0.5 align-middle text-xs font-bold text-cream-50">Today</span>@endif
                                    </th>
                                    <td class="px-4 py-3 font-semibold text-ink">{{ $day['orders'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-ink">{{ $peso($day['sales']) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-teal-700">{{ $peso($day['payments']) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-danger">{{ $peso($day['expenses']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-cream-200 bg-cream-100">
                                <th scope="row" class="px-4 py-3 text-left font-bold text-ink">Week</th>
                                <td class="px-4 py-3 font-bold text-ink">{{ $week->sum('orders') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-ink">{{ $peso($week->sum('sales')) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-teal-700">{{ $peso($week->sum('payments')) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-danger">{{ $peso($week->sum('expenses')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

@include('partials.pending-dialogs')

{{-- Add expense --}}
<dialog
    id="expense-dialog"
    aria-labelledby="expense-dialog-title"
    data-reopen="{{ $errors->expenseForm->any() ? '1' : '' }}"
    data-old-expense-id=""
    data-old-amount="{{ old('amount') }}"
    data-old-description="{{ old('description') }}"
    data-today="{{ now()->format('M j, Y') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <div>
            <h2 id="expense-dialog-title" data-form-title class="font-display text-2xl font-bold leading-tight text-teal-900">Add Expense</h2>
            <p data-form-subtitle class="mt-0.5 text-sm font-semibold text-ink-soft">Recorded for today, {{ now()->format('M j, Y') }}</p>
        </div>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form method="POST" action="{{ route('expenses.store') }}" data-expense-form data-store-url="{{ route('expenses.store') }}" class="px-5 py-5" novalidate>
        @csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        <input type="hidden" name="expense_id" value="">

        <div class="space-y-5">
            <div>
                <label for="dashboard-expense-amount" class="block text-sm font-bold">Amount</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                    <input id="dashboard-expense-amount" name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" required class="w-full rounded-lg border-2 border-cream-200 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink focus:border-teal-500">
                </div>
                @error('amount', 'expenseForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="dashboard-expense-description" class="block text-sm font-bold">Description</label>
                <input id="dashboard-expense-description" name="description" type="text" maxlength="255" required autocomplete="off" placeholder="What was it for?" class="mt-1.5 w-full rounded-lg border-2 border-cream-200 bg-white px-4 py-3 text-base font-semibold text-ink focus:border-teal-500">
                @error('description', 'expenseForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" data-form-submit class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-snug text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60 disabled:shadow-none">Save Expense</button>
        </div>
    </form>
</dialog>

<dialog
    id="expense-confirm-dialog"
    aria-labelledby="expense-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="expense-confirm-title" data-confirm-title class="font-display text-2xl font-bold leading-tight text-teal-900">Add Expense?</h2>
        <p class="mt-2 text-ink-soft"><span data-confirm-verb>Record</span> <strong class="text-ink" data-confirm-amount></strong> for <strong class="break-words text-ink" data-confirm-description></strong>?</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-expense-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">Confirm</button>
        </div>
    </div>
</dialog>

@endsection
