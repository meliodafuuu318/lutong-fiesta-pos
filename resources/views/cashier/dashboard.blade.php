@extends('layouts.main')

@section('title', 'Dashboard · ' . config('app.name'))

@section('main')
@php
    $bag = $errors->createTransaction;
    $oldType = old('type', 'WALK IN');
    $itemErrors = collect($bag->get('transaction_items.*'))->flatten()->merge($bag->get('transaction_items'))->unique()->values();
    // Items already on the receipt when the server sent the form back with errors: [daily item id => quantity]
    $initialLines = collect(old('transaction_items', []))
        ->map(fn ($line) => (int) ($line['quantity'] ?? 0))
        ->filter(fn ($quantity) => $quantity > 0);
    $typeLabel = 'block rounded-lg border-2 border-cream-200 bg-white px-3 py-3 text-center font-bold peer-checked:border-teal-700 peer-checked:bg-teal-700 peer-checked:text-cream-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-teal-500';
@endphp

<div class="w-full pb-24 lg:pb-8">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">New Transaction</h1>
        <p class="font-semibold text-ink-soft">{{ now()->format('l, F j, Y') }}</p>
    </div>

    @if ($bag->has('transaction'))
        <p role="alert" class="mt-4 rounded-lg border-2 border-danger bg-cream-50 px-4 py-3 font-semibold text-danger">{{ $bag->first('transaction') }}</p>
    @endif

    <form
        id="cashier-order-form"
        method="POST"
        action="{{ route('transactions.store') }}"
        data-initial-lines="{{ $initialLines->toJson() }}"
        class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start"
    >
        @csrf
        {{-- The receipt's lines are written in here as transaction_items[...] fields --}}
        <div data-line-inputs hidden></div>

        {{-- ============ Left: the menu, then how the order is taken ============ --}}
        <div class="min-w-0 space-y-6">
            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Menu</h2>
                    <p class="text-sm text-ink-soft">Tap an item to add it to the receipt.</p>
                </div>
                <div class="px-5 py-4">
                    @if ($categories->isEmpty())
                        <p class="rounded-xl border-2 border-dashed border-cream-200 px-6 py-10 text-center text-ink-soft">
                            There's nothing on today's menu yet. Ask an admin to add items to the menu.
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
                                    <ul class="grid grid-cols-2 gap-3 xl:grid-cols-3">
                                        @foreach ($menuByCategory[$category->id] as $dailyItem)
                                            <li>
                                                @if ($dailyItem->status === 'SOLD OUT')
                                                    <div class="flex h-full flex-col justify-between gap-3 rounded-xl border-2 border-t-4 border-ink/10 border-t-ink/20 bg-ink/5 p-3 opacity-60 grayscale">
                                                        <div>
                                                            <p class="font-display text-base font-bold leading-snug text-ink">{{ $dailyItem->item->name }}</p>
                                                            <span class="mt-1 inline-block rounded bg-ink px-2 py-0.5 text-xs font-bold text-cream-50">Sold out</span>
                                                        </div>
                                                        <p class="font-bold text-teal-700">₱{{ number_format($dailyItem->price, 2) }}</p>
                                                    </div>
                                                @else
                                                    <button
                                                        type="button"
                                                        data-pick-item
                                                        data-id="{{ $dailyItem->id }}"
                                                        data-name="{{ $dailyItem->item->name }}"
                                                        data-price="{{ $dailyItem->price }}"
                                                        aria-label="Add {{ $dailyItem->item->name }}, ₱{{ number_format($dailyItem->price, 2) }}"
                                                        class="relative flex h-full w-full cursor-pointer flex-col justify-between gap-3 rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-white p-3 text-left transition hover:border-teal-500 hover:border-t-amber-500 active:scale-[0.98] data-[selected=true]:border-teal-600 data-[selected=true]:border-t-teal-600 data-[selected=true]:bg-teal-100/50"
                                                    >
                                                        <span class="pr-6 font-display text-base font-bold leading-snug text-ink">{{ $dailyItem->item->name }}</span>
                                                        <span class="font-bold text-teal-700">₱{{ number_format($dailyItem->price, 2) }}</span>
                                                        <span data-line-badge hidden class="absolute right-2 top-2 min-w-6 rounded-full bg-teal-700 px-1.5 py-0.5 text-center text-xs font-bold text-cream-50"></span>
                                                    </button>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Order type, with the fields that go with it --}}
            <section class="rounded-xl border-2 border-cream-200 bg-cream-50">
                <div class="border-b-2 border-cream-200 px-5 py-3">
                    <h2 class="font-display text-xl font-bold text-teal-900">Order Details</h2>
                </div>
                <div class="space-y-5 px-5 py-4">
                    <div>
                        <fieldset class="grid grid-cols-2 gap-2">
                            <legend class="sr-only">Transaction type</legend>
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="WALK IN" class="peer sr-only" @checked($oldType === 'WALK IN')>
                                <span class="{{ $typeLabel }}">Walk In</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="DELIVERY" class="peer sr-only" @checked($oldType === 'DELIVERY')>
                                <span class="{{ $typeLabel }}">Delivery</span>
                            </label>
                        </fieldset>
                        @error('type', 'createTransaction')
                            <p class="mt-2 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Delivery: address --}}
                    <div data-section="DELIVERY" hidden>
                        <label for="delivery-address" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Delivery Address</label>
                        <textarea
                            id="delivery-address" name="address" rows="3" maxlength="500" required
                            class="mt-1 w-full rounded-lg border-2 bg-white px-3 py-2.5 text-base font-semibold text-ink @error('address', 'createTransaction') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                        >{{ old('address') }}</textarea>
                        @error('address', 'createTransaction')
                            <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Payment: always for a walk in, optional for a delivery that was pre-paid --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <p data-payment-optional hidden class="text-sm text-ink-soft sm:col-span-2">Payment is optional. Only fill it in if the customer already paid for the delivery.</p>
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

                    {{-- Note (walk in and delivery) --}}
                    <div>
                        <label for="transaction-note" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Note <span class="font-semibold normal-case tracking-normal">(optional)</span></label>
                        <textarea
                            id="transaction-note" name="note" rows="2" maxlength="500"
                            class="mt-1 w-full rounded-lg border-2 bg-white px-3 py-2.5 text-base font-semibold text-ink @error('note', 'createTransaction') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                        >{{ old('note') }}</textarea>
                        @error('note', 'createTransaction')
                            <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ============ Right: the receipt, and the button that creates the transaction ============ --}}
        <aside id="receipt" class="min-w-0 scroll-mt-20 lg:sticky lg:top-20">
            <div class="rounded-xl border-2 border-cream-200 bg-white p-5 shadow-sm">
                <div class="flex justify-center">
                    <img src="{{ asset('images/favicon.png') }}" alt="" class="h-14 w-auto">
                </div>
                <div class="mt-4 text-center">
                    <p class="font-display text-lg font-bold text-teal-900">Receipt</p>
                    <p class="mt-1 text-xs text-ink-soft">{{ now()->format('M j, Y') }}</p>
                </div>

                <div class="mt-4 border-t border-dashed border-cream-200 pt-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Items</p>
                    <p data-receipt-empty class="mt-2 text-sm text-ink-soft">No items yet. Tap an item on the menu to add it.</p>
                    <ul data-receipt-items class="mt-2 space-y-2"></ul>
                    <p data-items-error role="alert" class="mt-3 text-sm font-semibold text-danger" @if ($itemErrors->isEmpty()) hidden @endif>{{ $itemErrors->implode(' ') }}</p>
                </div>

                <div class="mt-4 space-y-1 border-t border-dashed border-cream-200 pt-4 text-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-ink-soft">Total</p>
                        <p class="font-display text-xl font-bold text-teal-700" data-receipt-total>₱0.00</p>
                    </div>
                    <div data-receipt-payment class="space-y-1">
                        <div class="flex items-center justify-between">
                            <p class="text-ink-soft" data-receipt-method>Cash</p>
                            <p class="font-semibold text-ink" data-receipt-amount>—</p>
                        </div>
                        <div class="flex items-center justify-between" data-receipt-diff hidden>
                            <p class="text-ink-soft" data-receipt-diff-label>Change</p>
                            <p class="font-bold text-ink" data-receipt-diff-value>₱0.00</p>
                        </div>
                    </div>
                    <div data-receipt-delivery hidden>
                        <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Deliver To</p>
                        <p class="mt-1 break-words text-ink" data-receipt-address>—</p>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="mt-4 w-full cursor-pointer rounded-lg bg-amber-500 px-5 py-3 font-display text-lg font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
            >Create Transaction</button>
        </aside>
    </form>
</div>

{{-- Phones: the receipt is below everything, so keep the total in reach --}}
<div class="fixed inset-x-0 bottom-0 z-10 flex h-20 items-center justify-between gap-3 border-t-2 border-cream-200 bg-cream-50 px-4 transition-[left] duration-200 sm:px-8 lg:hidden">
    <p class="text-sm font-semibold leading-tight text-ink-soft">
        <span data-receipt-count>No items</span>
        <span class="block font-display text-2xl font-bold text-teal-700" data-receipt-total>₱0.00</span>
    </p>
    <a href="#receipt" class="rounded-lg border-2 border-teal-700 px-5 py-3 font-display text-base font-bold leading-tight text-teal-700 hover:bg-teal-700 hover:text-cream-50">View Receipt</a>
</div>

{{-- How many? --}}
<dialog
    id="quantity-dialog"
    aria-labelledby="quantity-dialog-title"
    class="m-auto w-[min(22rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <p class="text-sm font-bold uppercase tracking-wide text-ink-soft">Add to receipt</p>
        <h2 id="quantity-dialog-title" data-quantity-name class="font-display text-2xl font-bold leading-tight text-teal-900"></h2>
        <p data-quantity-price class="mt-0.5 font-bold text-teal-700"></p>
        <p data-quantity-existing hidden class="mt-1 text-sm font-semibold text-ink-soft"></p>

        <label for="quantity-input" class="mt-5 block text-sm font-bold">Quantity</label>
        <div class="mt-1.5 flex items-center gap-2">
            <button type="button" data-quantity-minus aria-label="Decrease quantity" class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-lg border-2 border-cream-200 bg-white text-2xl font-bold leading-none hover:border-teal-500">−</button>
            <input
                id="quantity-input" data-quantity-input type="number" inputmode="numeric" min="1" max="99" value="1"
                class="w-full min-w-0 rounded-lg border-2 border-cream-200 bg-white py-3 text-center text-xl font-bold text-ink [appearance:textfield] focus:border-teal-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            >
            <button type="button" data-quantity-plus aria-label="Increase quantity" class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-lg border-2 border-cream-200 bg-white text-2xl font-bold leading-none hover:border-teal-500">+</button>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-dashed border-cream-200 pt-3">
            <p class="font-semibold text-ink-soft">Subtotal</p>
            <p data-quantity-total class="font-display text-xl font-bold text-ink"></p>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-quantity-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-bold text-ink hover:bg-amber-400">Add to Receipt</button>
        </div>
    </div>
</dialog>

@include('transactions.partials.confirm-dialog')
@endsection
