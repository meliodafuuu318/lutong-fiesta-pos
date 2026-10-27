@extends('layouts.main')

@section('title', 'Dashboard · ' . config('app.name'))

@section('main')
@php
    $pendingCount = $pendingOrders->count();
    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);
@endphp

{{-- Same pending orders as the admin dashboard, but the address and the balance to collect come first --}}
<div class="w-full pb-8" data-pending>
    <div class="flex flex-wrap items-baseline justify-between gap-x-4">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">
            Pending Orders
            <span class="ml-1 inline-block min-w-7 rounded-full px-2 text-center align-middle text-base {{ $pendingCount > 0 ? 'bg-amber-500 text-ink' : 'bg-ink/10 text-ink-soft' }}">{{ $pendingCount }}</span>
        </h1>
        <p class="font-semibold text-ink-soft">{{ now()->format('l, F j, Y') }}</p>
    </div>

    @if ($pendingOrders->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            You're all caught up. There are no pending deliveries or payments.
        </p>
    @else
        <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Filter pending orders">
            @foreach ([
                ['all', 'All', $pendingCount],
                ['delivery', 'To deliver', $pendingDeliveryCount],
                ['payment', 'To collect', $pendingPaymentCount],
            ] as [$key, $label, $count])
                <button
                    type="button"
                    data-pending-filter="{{ $key }}"
                    aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}"
                    class="cursor-pointer rounded-full border-2 border-cream-200 bg-cream-50 px-4 py-2 text-sm font-bold text-ink hover:border-teal-500 aria-pressed:border-ink aria-pressed:bg-ink aria-pressed:text-cream-50"
                >{{ $label }} <span class="opacity-70">{{ $count }}</span></button>
            @endforeach
        </div>

        <ul class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($pendingOrders as $order)
                @php
                    $deliveryPending = $order->delivery?->status === 'PENDING';
                    $paymentPending = $order->payment_status === 'PENDING';
                    $paid = $order->amountPaid();
                    $due = $order->outstandingBalance();
                    $address = $order->delivery?->address;
                @endphp
                <li
                    data-pending-card
                    data-delivery="{{ $deliveryPending ? '1' : '0' }}"
                    data-payment="{{ $paymentPending ? '1' : '0' }}"
                    class="flex flex-col rounded-2xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-bold text-ink-soft">{{ $order->reference_number }}</p>
                            <p class="mt-0.5 text-xs font-semibold text-ink-soft">Ordered {{ $order->transaction_date->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap justify-end gap-1">
                            @if ($deliveryPending)
                                <span class="whitespace-nowrap rounded-full bg-amber-400/30 px-2.5 py-1 text-xs font-bold text-ink">To deliver</span>
                            @endif
                            @if ($paymentPending)
                                <span class="whitespace-nowrap rounded-full bg-danger/10 px-2.5 py-1 text-xs font-bold text-danger">To collect</span>
                            @endif
                        </div>
                    </div>

                    {{-- Where to go --}}
                    <div class="mt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Deliver to</p>
                        <p class="mt-1 flex gap-2 font-display text-xl font-bold leading-snug text-ink sm:text-2xl">
                            <svg class="mt-1 size-5 shrink-0 text-teal-700 sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="min-w-0 break-words">{{ $address ?: 'No address on file' }}</span>
                        </p>
                    </div>

                    {{-- What to collect --}}
                    <div @class(['mt-4 rounded-xl px-4 py-3', 'bg-danger/10' => $due > 0, 'bg-teal-100' => $due <= 0])>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">{{ $due > 0 ? 'Balance to collect' : 'Fully paid' }}</p>
                        <p @class(['font-display text-3xl font-bold leading-tight sm:text-4xl', 'text-danger' => $due > 0, 'text-teal-900' => $due <= 0])>{{ $peso($due) }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-ink-soft">
                            @if ($due > 0 && $paid > 0)
                                {{ $peso($paid) }} paid of {{ $peso($order->total) }}
                            @else
                                Order total {{ $peso($order->total) }}
                            @endif
                        </p>
                    </div>

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
                                class="min-h-14 cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
                            >Add Payment</button>
                        @endif
                        @if ($deliveryPending)
                            <button
                                type="button"
                                data-pending-deliver
                                data-id="{{ $order->id }}"
                                data-reference="{{ $order->reference_number }}"
                                data-address="{{ $address }}"
                                class="min-h-14 cursor-pointer rounded-lg border-2 border-teal-700 px-4 py-3 font-display text-lg font-bold leading-tight text-teal-700 transition hover:bg-teal-700 hover:text-cream-50"
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

@include('partials.pending-dialogs')
@endsection
