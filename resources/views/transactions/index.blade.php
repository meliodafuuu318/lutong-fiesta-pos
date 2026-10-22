@extends('layouts.main')

@section('title', 'Transactions · ' . config('app.name'))

@section('main')
<div class="mx-auto max-w-7xl">
    <h1 class="font-display text-3xl font-bold text-teal-900">Transactions</h1>

    @if ($transactions->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            There are no transactions yet.
        </p>
    @else
        <div class="mt-6 overflow-x-auto rounded-xl border-2 border-cream-200 bg-cream-50">
            <table class="w-full min-w-[62rem] text-center">
                <caption class="sr-only">Transactions, newest first</caption>
                <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                    <tr>
                        <th scope="col" class="px-3 py-3">Reference Number</th>
                        <th scope="col" class="px-3 py-3">Type</th>
                        <th scope="col" class="px-3 py-3">Total</th>
                        <th scope="col" class="px-3 py-3">Balance</th>
                        <th scope="col" class="px-3 py-3">Transaction Status</th>
                        <th scope="col" class="px-3 py-3">Delivery Status</th>
                        <th scope="col" class="px-3 py-3">Payment Status</th>
                        <th scope="col" class="px-3 py-3">Transaction Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60">
                            <th scope="row" class="whitespace-nowrap px-3 py-3 font-mono text-sm font-bold text-ink">{{ $transaction->reference_number }}</th>
                            <td class="whitespace-nowrap px-3 py-3 font-semibold text-ink">{{ \Illuminate\Support\Str::title(strtolower($transaction->type)) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 font-bold text-teal-700">₱{{ number_format($transaction->total, 2) }}</td>
                            <td @class([
                                'whitespace-nowrap px-3 py-3 font-bold',
                                'text-danger' => $transaction->hasOutstandingBalance(),
                                'text-ink-soft' => ! $transaction->hasOutstandingBalance(),
                            ])>₱{{ number_format($transaction->outstandingBalance(), 2) }}</td>
                            <td class="px-3 py-3"><x-status-badge :status="$transaction->status" /></td>
                            <td class="px-3 py-3">
                                @if ($transaction->delivery)
                                    <x-status-badge :status="$transaction->delivery->status" />
                                @else
                                    <span class="text-ink-soft" aria-label="Not applicable">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3"><x-status-badge :status="$transaction->payment_status" /></td>
                            <td class="whitespace-nowrap px-3 py-3 font-semibold text-ink">
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
