@extends('layouts.main')

@section('title', 'Transaction Details · ' . config('app.name'))

@section('main')
@php
    $deliveryCompleted = $transaction->type === 'DELIVERY'
        ? $transaction->delivery?->status === 'DELIVERED'
        : $transaction->status === 'COMPLETED';
    $canCancel = ! $transaction->isVoid() && ! $deliveryCompleted;
    $canRefund = ! $transaction->isVoid() && $deliveryCompleted;
@endphp
<div class="w-full">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous(route('transactions.index')) }}" class="flex size-10 items-center justify-center rounded-lg text-ink-soft hover:bg-cream-100 hover:text-ink">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Transaction Details</h1>
    </div>

    @if ($canCancel || $canRefund)
        <div class="mt-4 flex justify-end">
            @if ($canCancel)
                <button type="button" data-transaction-cancel-open class="cursor-pointer rounded-lg border-2 border-danger px-4 py-2.5 font-bold text-danger hover:bg-danger hover:text-cream-50">Cancel Transaction</button>
            @else
                <button type="button" data-transaction-refund-open class="cursor-pointer rounded-lg border-2 border-danger px-4 py-2.5 font-bold text-danger hover:bg-danger hover:text-cream-50">Return / Refund</button>
            @endif
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">General Info</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 px-5 py-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Reference Number</p>
                        <p class="mt-1 font-mono text-base font-bold text-ink">{{ $transaction->reference_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Type</p>
                        <p class="mt-1 text-base font-semibold text-ink">{{ \Illuminate\Support\Str::title(strtolower($transaction->type)) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Total</p>
                        <p class="mt-1 text-base font-bold text-teal-700">₱{{ number_format($transaction->total, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Transaction Date</p>
                        <p class="mt-1 text-base font-semibold text-ink">{{ $transaction->transaction_date->format('M j, Y') }}</p>
                        <p class="text-sm font-semibold text-ink-soft">{{ $transaction->transaction_date->format('g:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Transaction Status</p>
                        <div class="mt-1"><x-status-badge :status="$transaction->status" /></div>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Payment Status</p>
                        <div class="mt-1"><x-status-badge :status="$transaction->payment_status" /></div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Delivery</h2>
                </div>
                <div class="px-5 py-4">
                    @if ($transaction->type === 'DELIVERY' && $transaction->delivery)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Delivery Address</p>
                                <p class="mt-1 text-base font-semibold text-ink">{{ $transaction->delivery->address }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Delivery Status</p>
                                <div class="mt-1"><x-status-badge :status="$transaction->delivery->status" /></div>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-ink-soft">Not applicable for this transaction.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Payment</h2>
                </div>
                <div class="px-5 py-4">
                    @forelse ($payments as $payment)
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <div>
                                <p class="text-base font-semibold text-ink">{{ $payment->payment_method }}</p>
                                <p class="text-sm text-ink-soft">Payment amount: ₱{{ number_format($payment->payment_amount, 2) }}</p>
                            </div>
                        </div>
                        @if (! $loop->last)
                            <div class="border-t border-cream-200"></div>
                        @endif
                    @empty
                        <p class="text-sm text-ink-soft">No payments recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="min-w-0 lg:col-span-1 lg:sticky lg:top-20">
            <div class="rounded-xl border-2 border-cream-200 bg-white p-5 shadow-sm">
                <div class="flex justify-center">
                    <img src="{{ asset('images/favicon.png') }}" alt="Lutong Fiesta" class="h-14 w-auto">
                </div>
                <div class="mt-4 text-center">
                    <p class="font-mono text-sm font-bold text-ink">{{ $transaction->reference_number }}</p>
                    <p class="mt-1 text-xs text-ink-soft">{{ $transaction->transaction_date->format('M j, Y g:i A') }}</p>
                </div>
                <div class="mt-4 border-t border-dashed border-cream-200 pt-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Items</p>
                    <div class="mt-2 space-y-1">
                        @foreach ($transaction->transactionItems as $item)
                            <div class="flex items-start justify-between gap-2 text-sm">
                                <p class="text-ink">x{{ $item->quantity }} {{ $item->dailyItem?->item?->name ?? 'Item' }}</p>
                                <p class="font-semibold text-ink">₱{{ number_format($item->subtotal, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 border-t border-dashed border-cream-200 pt-4 space-y-1 text-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-ink-soft">Total</p>
                        <p class="font-bold text-ink">₱{{ number_format($transaction->total, 2) }}</p>
                    </div>
                </div>
                @if ($transaction->type === 'DELIVERY' && $transaction->delivery)
                    <div class="mt-4 border-t border-dashed border-cream-200 pt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Delivery</p>
                        <p class="mt-1 text-sm text-ink">{{ $transaction->delivery->address }}</p>
                        <p class="mt-1 text-xs font-semibold text-ink-soft">Status: {{ $transaction->delivery->status }}</p>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>

@if ($canCancel)
    <dialog id="transaction-cancel-dialog" class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50">
        <form method="POST" action="{{ route('transactions.cancel', $transaction) }}" class="p-5" data-transaction-action-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_to" value="{{ url()->previous(route('transactions.index')) }}">
            <h2 class="font-display text-2xl font-bold leading-tight text-teal-900">Cancel Transaction?</h2>
            <p class="mt-2 text-ink-soft">This will cancel <strong class="font-mono text-ink">{{ $transaction->reference_number }}</strong> and mark its payment and delivery as cancelled.</p>
            <div class="mt-5 grid grid-cols-2 gap-2">
                <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Keep</button>
                <button type="submit" class="cursor-pointer rounded-lg bg-danger px-4 py-2.5 font-bold text-cream-50 hover:brightness-110">Cancel Transaction</button>
            </div>
        </form>
    </dialog>
@elseif ($canRefund)
    <dialog id="transaction-refund-dialog" class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50">
        <form method="POST" action="{{ route('transactions.refund', $transaction) }}" class="p-5" data-transaction-action-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_to" value="{{ url()->previous(route('transactions.index')) }}">
            <h2 class="font-display text-2xl font-bold leading-tight text-teal-900">Return / Refund?</h2>
            <p class="mt-2 text-ink-soft">This will mark <strong class="font-mono text-ink">{{ $transaction->reference_number }}</strong> as returned/refunded and update its payment and delivery statuses.</p>
            <div class="mt-5 grid grid-cols-2 gap-2">
                <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Keep</button>
                <button type="submit" class="cursor-pointer rounded-lg bg-danger px-4 py-2.5 font-bold text-cream-50 hover:brightness-110">Return / Refund</button>
            </div>
        </form>
    </dialog>
@endif
@endsection
