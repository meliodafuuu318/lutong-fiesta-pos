{{-- "Create Transaction?" confirmation, shared by the admin create page and the cashier dashboard --}}
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
            <div data-confirm-note-wrap hidden>
                <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Note</p>
                <p class="mt-1 break-words text-ink" data-confirm-note></p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-create-transaction-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:opacity-50">Create Transaction</button>
        </div>
    </div>
</dialog>
