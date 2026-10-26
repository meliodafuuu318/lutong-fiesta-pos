@extends('layouts.main')

@section('title', 'Create Transaction · ' . config('app.name'))

@section('main')
@php
    $bag = $errors->createTransaction;
    $oldType = old('type', 'WALK IN');
    $itemErrors = collect($bag->get('transaction_items.*'))->flatten()->merge($bag->get('transaction_items'))->unique()->values();
@endphp

<div class="w-full pb-28">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous(route('transactions.index')) }}" aria-label="Back" class="flex size-10 items-center justify-center rounded-lg text-ink-soft hover:bg-cream-100 hover:text-ink">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Create Transaction</h1>
    </div>

    @if ($bag->has('transaction'))
        <p role="alert" class="mt-4 rounded-lg border-2 border-danger bg-cream-50 px-4 py-3 font-semibold text-danger">{{ $bag->first('transaction') }}</p>
    @endif

    <form id="create-transaction-form" method="POST" action="{{ route('transactions.store') }}" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        @csrf

        <div class="min-w-0 space-y-6 lg:col-span-2">
            {{-- Type --}}
            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Transaction Type</h2>
                </div>
                <div class="px-5 py-4">
                    <fieldset class="grid grid-cols-2 gap-2">
                        <legend class="sr-only">Transaction type</legend>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="WALK IN" class="peer sr-only" @checked($oldType === 'WALK IN')>
                            <span class="block rounded-lg border-2 border-cream-200 bg-white px-3 py-3 text-center font-bold peer-checked:border-teal-700 peer-checked:bg-teal-700 peer-checked:text-cream-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-teal-500">Walk In</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="DELIVERY" class="peer sr-only" @checked($oldType === 'DELIVERY')>
                            <span class="block rounded-lg border-2 border-cream-200 bg-white px-3 py-3 text-center font-bold peer-checked:border-teal-700 peer-checked:bg-teal-700 peer-checked:text-cream-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-teal-500">Delivery</span>
                        </label>
                    </fieldset>
                    @error('type', 'createTransaction')
                        <p class="mt-2 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- Items --}}
            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Items</h2>
                </div>
                <div class="px-5 py-4">
                    <p data-items-error role="alert" class="mb-3 text-sm font-semibold text-danger" @if ($itemErrors->isEmpty()) hidden @endif>{{ $itemErrors->implode(' ') }}</p>

                    @if ($categories->isEmpty())
                        <p class="rounded-xl border-2 border-dashed border-cream-200 px-6 py-10 text-center text-ink-soft">
                            There's nothing on today's menu yet. Add items to the menu from the Menu page first.
                        </p>
                    @else
                        <div data-tabs>
                            <div role="tablist" aria-label="Item categories" class="flex gap-2 overflow-x-auto pb-3">
                                @foreach ($categories as $category)
                                    <button
                                        type="button"
                                        role="tab"
                                        id="tab-{{ $category->id }}"
                                        aria-controls="panel-{{ $category->id }}"
                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                        tabindex="{{ $loop->first ? 0 : -1 }}"
                                        class="shrink-0 cursor-pointer rounded-full border-2 border-cream-200 bg-white px-5 py-2 font-bold text-ink hover:border-teal-500 aria-selected:border-teal-700 aria-selected:bg-teal-700 aria-selected:text-cream-50"
                                    >{{ $category->name }}</button>
                                @endforeach
                            </div>

                            @foreach ($categories as $category)
                                <div
                                    role="tabpanel"
                                    id="panel-{{ $category->id }}"
                                    aria-labelledby="tab-{{ $category->id }}"
                                    tabindex="0"
                                    @unless ($loop->first) hidden @endunless
                                >
                                    <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($menuByCategory[$category->id] as $dailyItem)
                                            @php($initial = (int) old("transaction_items.{$dailyItem->id}.quantity", 0))
                                            <li>
                                                {{-- card:start --}}
                                                @if ($dailyItem->status === 'SOLD OUT')
                                                    <div class="flex h-full flex-col justify-between gap-3 rounded-xl border-2 border-t-4 border-ink/10 border-t-ink/20 bg-ink/5 p-3 opacity-60 grayscale">
                                                        <div>
                                                            <p class="font-display text-base font-bold leading-snug text-ink">{{ $dailyItem->item->name }}</p>
                                                            <span class="mt-1 inline-block rounded bg-ink px-2 py-0.5 text-xs font-bold text-cream-50">Sold out</span>
                                                        </div>
                                                        <p class="font-bold text-teal-700">₱{{ number_format($dailyItem->price, 2) }}</p>
                                                    </div>
                                                @else
                                                    <div
                                                        data-menu-item
                                                        data-name="{{ $dailyItem->item->name }}"
                                                        data-price="{{ $dailyItem->price }}"
                                                        data-initial="{{ $initial }}"
                                                        class="flex h-full flex-col justify-between gap-3 rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-white p-3 data-[selected=true]:border-teal-600 data-[selected=true]:border-t-teal-600 data-[selected=true]:bg-teal-100/50"
                                                    >
                                                        <div>
                                                            <p class="font-display text-base font-bold leading-snug text-ink">{{ $dailyItem->item->name }}</p>
                                                            <p class="mt-0.5 font-bold text-teal-700">₱{{ number_format($dailyItem->price, 2) }}</p>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <button type="button" data-qty-minus aria-label="Decrease quantity of {{ $dailyItem->item->name }}" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg border-2 border-cream-200 bg-white text-xl font-bold leading-none hover:border-teal-500">−</button>
                                                            <input
                                                                type="number" data-qty inputmode="numeric" min="0" max="99" value="0"
                                                                aria-label="Quantity of {{ $dailyItem->item->name }}"
                                                                class="w-full min-w-0 rounded-lg border-2 border-cream-200 bg-white py-2 text-center font-bold text-ink [appearance:textfield] focus:border-teal-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                            >
                                                            <button type="button" data-qty-plus aria-label="Increase quantity of {{ $dailyItem->item->name }}" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg border-2 border-cream-200 bg-white text-xl font-bold leading-none hover:border-teal-500">+</button>
                                                        </div>
                                                        {{-- Only sent while the quantity is above zero --}}
                                                        <input type="hidden" name="transaction_items[{{ $dailyItem->id }}][daily_item_id]" value="{{ $dailyItem->id }}" disabled>
                                                        <input type="hidden" name="transaction_items[{{ $dailyItem->id }}][quantity]" value="" disabled>
                                                    </div>
                                                @endif
                                                {{-- card:end --}}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Payment (walk in) --}}
            <section data-section="WALK IN" class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Payment</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 px-5 py-4 sm:grid-cols-2">
                    <div>
                        <label for="payment-method" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Payment Method</label>
                        <select
                            id="payment-method" name="payment_method" required
                            class="mt-1 w-full cursor-pointer rounded-lg border-2 bg-white px-3 py-2.5 text-base font-semibold text-ink @error('payment_method', 'createTransaction') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                        >
                            <option value="CASH" @selected(old('payment_method', 'CASH') === 'CASH')>Cash</option>
                            <option value="GCASH" @selected(old('payment_method') === 'GCASH')>GCash</option>
                            <option value="BANK TRANSFER" @selected(old('payment_method') === 'BANK TRANSFER')>Bank Transfer</option>
                        </select>
                        @error('payment_method', 'createTransaction')
                            <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment-amount" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Amount Paid</label>
                        <div class="mt-1 flex gap-2">
                            <div class="relative min-w-0 flex-1">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center font-bold text-ink-soft">₱</span>
                                <input
                                    id="payment-amount" name="payment_amount" type="number" inputmode="decimal" step="0.01" min="0" required
                                    value="{{ old('payment_amount') }}"
                                    class="w-full rounded-lg border-2 bg-white py-2.5 pl-8 pr-3 text-base font-bold text-ink @error('payment_amount', 'createTransaction') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                                >
                            </div>
                            <button type="button" data-use-total class="shrink-0 cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 text-sm font-bold text-teal-700 hover:border-teal-500">Exact</button>
                        </div>
                        @error('payment_amount', 'createTransaction')
                            <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- Delivery --}}
            <section data-section="DELIVERY" class="rounded-xl border-2 border-cream-200 bg-cream-50" hidden>
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Delivery</h2>
                </div>
                <div class="px-5 py-4">
                    <label for="delivery-address" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Delivery Address</label>
                    <textarea
                        id="delivery-address" name="address" rows="3" maxlength="500" required
                        class="mt-1 w-full rounded-lg border-2 bg-white px-3 py-2.5 text-base font-semibold text-ink @error('address', 'createTransaction') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                    >{{ old('address') }}</textarea>
                    @error('address', 'createTransaction')
                        <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-ink-soft">No payment is taken when a delivery is created.</p>
                </div>
            </section>
        </div>

        {{-- Live summary --}}
        <aside class="min-w-0 lg:col-span-1 lg:sticky lg:top-20">
            <div class="rounded-xl border-2 border-cream-200 bg-white p-5 shadow-sm">
                <div class="flex justify-center">
                    <img src="{{ asset('images/favicon.png') }}" alt="" class="h-14 w-auto">
                </div>
                <div class="mt-4 text-center">
                    <p class="font-display text-lg font-bold text-teal-900">New Transaction</p>
                    <p class="mt-1 text-xs text-ink-soft">{{ now()->format('M j, Y') }}</p>
                </div>
                <div class="mt-4 border-t border-dashed border-cream-200 pt-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Items</p>
                    <p data-summary-empty class="mt-2 text-sm text-ink-soft">No items yet.</p>
                    <div data-summary-items class="mt-2 space-y-1"></div>
                </div>
                <div class="mt-4 space-y-1 border-t border-dashed border-cream-200 pt-4 text-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-ink-soft">Total</p>
                        <p class="font-bold text-ink" data-total>₱0.00</p>
                    </div>
                    <div data-summary-payment class="space-y-1">
                        <div class="flex items-center justify-between">
                            <p class="text-ink-soft" data-payment-method-label>Cash</p>
                            <p class="font-semibold text-ink" data-payment-amount>—</p>
                        </div>
                        <div class="flex items-center justify-between" data-payment-diff hidden>
                            <p class="text-ink-soft" data-payment-diff-label>Change</p>
                            <p class="font-bold text-ink" data-payment-diff-value>₱0.00</p>
                        </div>
                    </div>
                    <div data-summary-delivery hidden>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Deliver To</p>
                        <p class="mt-1 break-words text-ink" data-delivery-address>—</p>
                    </div>
                </div>
            </div>
        </aside>
    </form>
</div>

{{-- Confirmation --}}
<dialog
    id="create-transaction-confirm-dialog"
    aria-labelledby="create-transaction-confirm-title"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(26rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="create-transaction-confirm-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Create Transaction?</h2>
        <p class="mt-1 text-sm font-semibold text-ink-soft" data-confirm-type></p>

        <div class="mt-4 space-y-1 border-t border-dashed border-cream-200 pt-4 text-sm" data-confirm-items></div>

        <div class="mt-4 space-y-1 border-t border-dashed border-cream-200 pt-4 text-sm">
            <div class="flex items-center justify-between">
                <p class="text-ink-soft">Total</p>
                <p class="font-bold text-ink" data-confirm-total></p>
            </div>
            <div data-confirm-payment class="space-y-1">
                <div class="flex items-center justify-between">
                    <p class="text-ink-soft" data-confirm-method></p>
                    <p class="font-semibold text-ink" data-confirm-amount></p>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-ink-soft" data-confirm-diff-label></p>
                    <p class="font-bold text-ink" data-confirm-diff-value></p>
                </div>
            </div>
            <div data-confirm-delivery hidden>
                <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Deliver To</p>
                <p class="mt-1 break-words text-ink" data-confirm-address></p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-create-transaction-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:opacity-50">Create Transaction</button>
        </div>
    </div>
</dialog>

{{-- Bottom action bar --}}
<div class="fixed inset-x-0 bottom-0 z-10 flex h-20 items-center justify-between gap-3 border-t-2 border-cream-200 bg-cream-50 px-4 transition-[left] duration-200 sm:px-8 lg:group-data-[menu=open]/shell:left-64">
    <p class="text-sm font-semibold leading-tight text-ink-soft">
        Total
        <span class="block font-display text-2xl font-bold text-teal-700" data-total>₱0.00</span>
    </p>
    <button
        type="submit"
        form="create-transaction-form"
        class="cursor-pointer rounded-lg bg-amber-500 px-5 py-3 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] sm:px-6 sm:text-lg"
    >Create Transaction</button>
</div>
@endsection
