{{-- Add payment, record-payment confirmation and mark-delivered dialogs.
     Used by every page with pending order cards (the admin and rider dashboards); resources/js/app.js wires them up. --}}
{{-- Add payment --}}
<dialog
    id="payment-dialog"
    aria-labelledby="payment-dialog-title"
    data-reopen="{{ $errors->addPayment->any() ? '1' : '' }}"
    data-old-transaction="{{ old('transaction_id') }}"
    data-old-method="{{ old('payment_method') }}"
    data-old-amount="{{ old('payment_amount') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <div class="min-w-0">
            <p class="text-sm font-bold uppercase tracking-wide text-ink-soft">Add payment</p>
            <h2 id="payment-dialog-title" data-payment-reference class="truncate font-mono text-lg font-bold leading-tight text-teal-900"></h2>
        </div>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form method="POST" data-payment-form data-url="{{ route('transactions.payments.store', '__ID__') }}" class="space-y-5 px-5 py-5" novalidate>
        @csrf
        <input type="hidden" name="transaction_id" value="">

        <dl class="grid grid-cols-3 gap-2 rounded-lg bg-white p-3 text-sm">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Total</dt>
                <dd data-payment-total class="mt-0.5 font-bold text-ink"></dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Paid</dt>
                <dd data-payment-paid class="mt-0.5 font-bold text-ink"></dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Due</dt>
                <dd data-payment-due class="mt-0.5 font-bold text-danger"></dd>
            </div>
        </dl>

        <div>
            <label for="payment-method" class="block text-sm font-bold">Payment method</label>
            <select
                id="payment-method" name="payment_method" required
                @class([
                    'mt-1.5 w-full cursor-pointer rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink',
                    'border-cream-200 focus:border-teal-500' => ! $errors->addPayment->has('payment_method'),
                    'border-danger' => $errors->addPayment->has('payment_method'),
                ])
            >
                <option value="CASH">Cash</option>
                <option value="GCASH">GCash</option>
                <option value="BANK TRANSFER">Bank Transfer</option>
            </select>
            @error('payment_method', 'addPayment')
                <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="payment-amount" class="block text-sm font-bold">Amount paid</label>
            <div class="mt-1.5 flex gap-2">
                <div class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                    <input
                        id="payment-amount" name="payment_amount" type="number" inputmode="decimal" step="0.01" min="0.01" required
                        @class([
                            'w-full rounded-lg border-2 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink',
                            'border-cream-200 focus:border-teal-500' => ! $errors->addPayment->has('payment_amount'),
                            'border-danger' => $errors->addPayment->has('payment_amount'),
                        ])
                    >
                </div>
                <button type="button" data-payment-exact class="shrink-0 cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 font-bold text-teal-700 hover:border-teal-500">Exact</button>
            </div>
            @error('payment_amount', 'addPayment')
                <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
            <p data-payment-hint class="mt-2 min-h-5 text-sm font-semibold text-ink-soft" aria-live="polite"></p>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-snug text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] disabled:cursor-wait disabled:opacity-60 disabled:shadow-none">Record</button>
        </div>
    </form>
</dialog>

{{-- Record payment confirmation --}}
<dialog
    id="payment-confirm-dialog"
    aria-labelledby="payment-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="payment-confirm-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Record this payment?</h2>
        <p class="mt-2 text-ink-soft">
            <strong class="text-ink" data-payment-confirm-amount></strong> by
            <strong class="text-ink" data-payment-confirm-method></strong> for
            <strong class="font-mono text-ink" data-payment-confirm-reference></strong>.
        </p>
        <p class="mt-1 text-sm font-semibold text-ink-soft" data-payment-confirm-note></p>
        <p class="mt-3 text-sm text-ink-soft">A recorded payment can't be edited or removed.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-payment-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">Record Payment</button>
        </div>
    </div>
</dialog>

{{-- Mark delivered --}}
<dialog
    id="deliver-dialog"
    aria-labelledby="deliver-dialog-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <form method="POST" data-deliver-form data-url="{{ route('transactions.deliver', '__ID__') }}" class="p-5">
        @csrf
        @method('PATCH')
        <h2 id="deliver-dialog-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Mark as delivered?</h2>
        <p class="mt-2 text-ink-soft"><strong class="font-mono text-ink" data-deliver-reference></strong> will be marked as delivered to:</p>
        <p class="mt-1 break-words font-semibold text-ink" data-deliver-address></p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">Delivered</button>
        </div>
    </form>
</dialog>
