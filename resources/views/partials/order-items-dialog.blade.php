{{-- Needs $order (with transactionItems.dailyItem.item loaded) and $peso. --}}
@php($lines = $order->transactionItems)
{{-- The order's items. Rendered with the page and kept hidden until the button above is
     tapped; opening and closing it is plain browser behaviour, so it works with no connection. --}}
<dialog
    data-order-items-dialog
    aria-labelledby="order-items-title-{{ $order->id }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(26rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <div class="min-w-0">
            <p class="text-sm font-bold uppercase tracking-wide text-ink-soft">Order items</p>
            <h2 id="order-items-title-{{ $order->id }}" class="truncate font-mono text-lg font-bold leading-tight text-teal-900">{{ $order->reference_number }}</h2>
        </div>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <div class="px-5 py-4">
        @if ($lines->isEmpty())
            <p class="text-sm text-ink-soft">No items on this order.</p>
        @else
            <ul class="space-y-3">
                @foreach ($lines as $line)
                    <li class="flex items-start justify-between gap-3">
                        <p class="min-w-0 break-words font-semibold text-ink">
                            @if ($line->quantity !== null)
                                <span class="font-display font-bold text-teal-700">{{ $line->quantity }}×</span>
                            @endif
                            {{ $line->dailyItem?->item?->name ?? 'Item no longer available' }}
                            @if ($line->quantity === null)
                                <span class="text-sm font-semibold text-ink-soft">(custom serving)</span>
                            @endif
                        </p>
                        <p class="shrink-0 font-semibold text-ink-soft">{{ $peso($line->subtotal) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4 flex items-center justify-between border-t border-dashed border-cream-200 pt-3">
            <p class="font-semibold text-ink-soft">Total</p>
            <p class="font-display text-xl font-bold text-ink">{{ $peso($order->total) }}</p>
        </div>

        @if (filled($order->note))
            <div class="mt-4 rounded-lg bg-amber-400/20 px-3 py-2">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Note</p>
                <p class="mt-0.5 whitespace-pre-line break-words font-semibold text-ink">{{ $order->note }}</p>
            </div>
        @endif

        <button type="button" data-dialog-close class="mt-5 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Close</button>
    </div>
</dialog>
