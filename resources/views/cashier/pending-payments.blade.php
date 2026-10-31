@extends('layouts.main')

@section('title', 'Pending Payments · ' . config('app.name'))

@section('main')
@php
    $pendingCount = $pendingOrders->count();
    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);
@endphp

{{-- Orders that still owe money, oldest first. Cancelling a duplicate is done from the order's details. --}}
<div class="w-full pb-8" data-pending>
    <div class="flex flex-wrap items-baseline justify-between gap-x-4">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">
            Pending Payments
            <span class="ml-1 inline-block min-w-7 rounded-full px-2 text-center align-middle text-base {{ $pendingCount > 0 ? 'bg-amber-500 text-ink' : 'bg-ink/10 text-ink-soft' }}">{{ $pendingCount }}</span>
        </h1>
        <p class="font-semibold text-ink-soft">{{ now()->format('l, F j, Y') }}</p>
    </div>

    @if ($pendingOrders->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            You're all caught up. Every order has been paid for.
        </p>
    @else
        <ul class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($pendingOrders as $order)
                @php
                    $paid = $order->amountPaid();
                    $due = $order->outstandingBalance();
                    $delivery = $order->type === 'DELIVERY';
                    $address = $order->delivery?->address;
                    $lines = $order->transactionItems;
                    $itemCount = $lines->sum(fn ($line) => $line->quantity ?? 1);
                @endphp
                <li
                    data-pending-card
                    class="flex flex-col rounded-2xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-bold text-ink-soft">{{ $order->reference_number }}</p>
                            <p class="mt-0.5 text-xs font-semibold text-ink-soft">Ordered {{ $order->transaction_date->diffForHumans() }}</p>
                        </div>
                        <span class="shrink-0 whitespace-nowrap rounded-full bg-teal-100 px-2.5 py-1 text-xs font-bold text-teal-900">{{ $delivery ? 'Delivery' : 'Walk in' }}</span>
                    </div>

                    @if ($delivery)
                        <p class="mt-3 flex gap-2 text-sm font-semibold text-ink">
                            <svg class="mt-0.5 size-4 shrink-0 text-teal-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="min-w-0 break-words">{{ $address ?: 'No address on file' }}</span>
                        </p>
                    @endif

                    {{-- What is still owed --}}
                    <div class="mt-4 rounded-xl bg-danger/10 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Balance to collect</p>
                        <p class="font-display text-3xl font-bold leading-tight text-danger sm:text-4xl">{{ $peso($due) }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-ink-soft">
                            @if ($paid > 0)
                                {{ $peso($paid) }} paid of {{ $peso($order->total) }}
                            @else
                                Order total {{ $peso($order->total) }}
                            @endif
                        </p>
                    </div>

                    <div class="mt-auto grid grid-cols-1 gap-2 pt-4">
                        <button
                            type="button"
                            data-pending-pay
                            data-id="{{ $order->id }}"
                            data-reference="{{ $order->reference_number }}"
                            data-total="{{ $order->total }}"
                            data-paid="{{ $paid }}"
                            data-due="{{ $due }}"
                            class="min-h-14 cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
                        >Add Payment</button>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                data-order-items-open
                                aria-haspopup="dialog"
                                class="min-h-12 cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2.5 font-display text-base font-bold leading-tight text-ink transition hover:border-teal-500"
                            >Items <span class="text-ink-soft">({{ $itemCount }})</span></button>
                            <a
                                href="{{ route('transactions.show', $order) }}"
                                class="flex min-h-12 items-center justify-center rounded-lg border-2 border-cream-200 bg-white px-3 py-2.5 text-center font-display text-base font-bold leading-tight text-ink transition hover:border-teal-500"
                            >Details</a>
                        </div>
                    </div>

                    @include('partials.order-items-dialog')
                </li>
            @endforeach
        </ul>
    @endif
</div>

@include('partials.pending-dialogs')
@endsection
