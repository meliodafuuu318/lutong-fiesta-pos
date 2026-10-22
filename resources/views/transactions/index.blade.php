@extends('layouts.main')

@section('title', 'Transactions · ' . config('app.name'))

@section('main')
<div class="w-full">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-3xl font-bold text-teal-900">Transactions</h1>
        <a
            href="{{ route('dashboard') }}"
            class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
        >Create Transaction</a>
    </div>

    <form method="GET" action="{{ route('transactions.index') }}" class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50 p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="transactions-search" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Search</label>
                <input
                    id="transactions-search"
                    name="search"
                    type="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search reference number..."
                    class="mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500"
                >
            </div>
            <div>
                <label for="transactions-type" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Type</label>
                <select id="transactions-type" name="type" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="" @selected(empty($type))>All</option>
                    <option value="WALK IN" @selected(($type ?? '') === 'WALK IN')>Walk In</option>
                    <option value="DELIVERY" @selected(($type ?? '') === 'DELIVERY')>Delivery</option>
                </select>
            </div>
            <div>
                <label for="transactions-date-from" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Date From</label>
                <input
                    id="transactions-date-from"
                    name="date_from"
                    type="date"
                    value="{{ $dateFrom ?? '' }}"
                    class="mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500"
                >
            </div>
            <div>
                <label for="transactions-date-to" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Date To</label>
                <input
                    id="transactions-date-to"
                    name="date_to"
                    type="date"
                    value="{{ $dateTo ?? '' }}"
                    class="mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500"
                >
            </div>
            <div>
                <label for="transactions-sort-by" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Sort By</label>
                <select id="transactions-sort-by" name="sort_by" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="transaction_date" @selected(($sortBy ?? 'transaction_date') === 'transaction_date')>Date</option>
                    <option value="total" @selected(($sortBy ?? 'transaction_date') === 'total')>Total</option>
                    <option value="reference_number" @selected(($sortBy ?? 'transaction_date') === 'reference_number')>Reference</option>
                    <option value="status" @selected(($sortBy ?? 'transaction_date') === 'status')>Status</option>
                    <option value="payment_status" @selected(($sortBy ?? 'transaction_date') === 'payment_status')>Payment</option>
                </select>
            </div>
            <div>
                <label for="transactions-sort-order" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Order</label>
                <select id="transactions-sort-order" name="sort_order" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="desc" @selected(($sortOrder ?? 'desc') === 'desc')>Descending</option>
                    <option value="asc" @selected(($sortOrder ?? 'desc') === 'asc')>Ascending</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('transactions.index') }}" class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2 text-sm font-bold hover:border-teal-500">Reset</a>
            <button type="submit" class="cursor-pointer rounded-lg bg-teal-700 px-4 py-2 text-sm font-bold text-cream-50 hover:bg-teal-600">Apply</button>
        </div>
    </form>

    @if ($transactions->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            No transactions found.
        </p>
    @else
        <div class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50">
            <table class="w-full table-auto text-center">
                <caption class="sr-only">Transactions</caption>
                <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                    <tr>
                        <th scope="col" class="px-4 py-3">Reference Number</th>
                        <th scope="col" class="px-4 py-3">Type</th>
                        <th scope="col" class="px-4 py-3">Total</th>
                        <th scope="col" class="px-4 py-3">Balance</th>
                        <th scope="col" class="px-4 py-3">Transaction Status</th>
                        <th scope="col" class="px-4 py-3">Delivery Status</th>
                        <th scope="col" class="px-4 py-3">Payment Status</th>
                        <th scope="col" class="px-4 py-3">Transaction Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60">
                            <th scope="row" class="whitespace-nowrap px-4 py-3 font-mono text-sm font-bold text-ink">{{ $transaction->reference_number }}</th>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">{{ \Illuminate\Support\Str::title(strtolower($transaction->type)) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-teal-700">₱{{ number_format($transaction->total, 2) }}</td>
                            <td @class([
                                'whitespace-nowrap px-4 py-3 font-bold',
                                'text-danger' => $transaction->hasOutstandingBalance(),
                                'text-ink-soft' => ! $transaction->hasOutstandingBalance(),
                            ])>₱{{ number_format($transaction->outstandingBalance(), 2) }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$transaction->status" /></td>
                            <td class="px-4 py-3">
                                @if ($transaction->delivery)
                                    <x-status-badge :status="$transaction->delivery->status" />
                                @else
                                    <span class="text-ink-soft" aria-label="Not applicable">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-status-badge :status="$transaction->payment_status" /></td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">
                                {{ $transaction->transaction_date->format('M j, Y') }}
                                <span class="block text-xs font-semibold text-ink-soft">{{ $transaction->transaction_date->format('g:i A') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links('pagination.lutong') }}
        </div>
    @endif
</div>
@endsection
