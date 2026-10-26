@extends('layouts.main')

@section('title', 'Menu · ' . config('app.name'))

@section('main')
<div class="pb-28">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Today's Menu</h1>
        <p class="font-semibold text-ink-soft">{{ now()->format('l, F j, Y') }}</p>
    </div>

    @if ($categories->isEmpty())
        <p class="mt-8 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            There's nothing on today's menu yet.
        </p>
    @else
        <div data-tabs class="mt-2">
            <div
                role="tablist"
                aria-label="Item categories"
                class="sticky top-16 z-10 -mx-4 flex gap-2 overflow-x-auto bg-cream-100 px-4 py-3 sm:-mx-8 sm:px-8"
            >
                @foreach ($categories as $category)
                    <button
                        type="button"
                        role="tab"
                        id="tab-{{ $category->id }}"
                        aria-controls="panel-{{ $category->id }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        tabindex="{{ $loop->first ? 0 : -1 }}"
                        class="shrink-0 cursor-pointer rounded-full border-2 border-cream-200 bg-cream-50 px-5 py-2 font-bold text-ink hover:border-teal-500 aria-selected:border-teal-700 aria-selected:bg-teal-700 aria-selected:text-cream-50"
                    >{{ $category->name }}</button>
                @endforeach
            </div>

            @foreach ($categories as $category)
                <div
                    role="tabpanel"
                    id="panel-{{ $category->id }}"
                    aria-labelledby="tab-{{ $category->id }}"
                    tabindex="0"
                    class="mt-2"
                    @unless ($loop->first) hidden @endunless
                >
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($menuByCategory[$category->id] as $dailyItem)
                            @php($soldOut = $dailyItem->status === 'SOLD OUT')
                            <li>
                                <button
                                    type="button"
                                    data-daily-item
                                    data-id="{{ $dailyItem->id }}"
                                    data-name="{{ $dailyItem->item->name }}"
                                    data-price="{{ $dailyItem->price }}"
                                    data-status="{{ $dailyItem->status }}"
                                    @class([
                                        'flex h-full min-h-32 w-full cursor-pointer flex-col justify-between gap-3 rounded-xl border-2 border-t-4 p-4 text-left transition hover:shadow-md active:scale-[0.98]',
                                        'border-cream-200 border-t-amber-500 bg-cream-50' => ! $soldOut,
                                        'border-ink/10 border-t-ink/20 bg-ink/5 opacity-60 grayscale' => $soldOut,
                                    ])
                                >
                                    <span>
                                        <span class="block font-display text-lg font-bold leading-snug text-ink">{{ $dailyItem->item->name }}</span>
                                        @if ($soldOut)
                                            <span class="mt-1 inline-block rounded bg-ink px-2 py-0.5 text-xs font-bold text-cream-50">Sold out</span>
                                        @endif
                                    </span>
                                    <span class="text-xl font-bold text-teal-700">₱{{ number_format($dailyItem->price, 2) }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Edit modal --}}
<dialog
    id="item-dialog"
    aria-labelledby="item-dialog-title"
    data-reopen="{{ old('daily_item_id') }}"
    data-old-price="{{ old('price') }}"
    data-old-status="{{ old('status') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <div class="min-w-0">
            <p class="text-sm font-bold uppercase tracking-wide text-ink-soft">Edit menu item</p>
            <h2 id="item-dialog-title" data-dialog-name class="font-display text-2xl font-bold leading-tight text-teal-900"></h2>
        </div>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form method="POST" data-update-form data-url="{{ route('daily-items.update', '__ID__') }}" class="space-y-5 px-5 py-5" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="daily_item_id">

        <div>
            <label for="item-price" class="block text-sm font-bold">Price</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                <input
                    id="item-price" name="price" type="number" inputmode="decimal" step="0.01" min="0" required
                    @class([
                        'w-full rounded-lg border-2 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink',
                        'border-cream-200 focus:border-teal-500' => ! $errors->has('price'),
                        'border-danger' => $errors->has('price'),
                    ])
                >
            </div>
            @error('price')
                <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <fieldset>
            <legend class="text-sm font-bold">Status</legend>
            <div class="mt-1.5 grid grid-cols-2 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="status" value="AVAILABLE" class="peer sr-only">
                    <span class="block rounded-lg border-2 border-cream-200 bg-white px-3 py-2.5 text-center font-bold peer-checked:border-teal-700 peer-checked:bg-teal-700 peer-checked:text-cream-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-teal-500">Available</span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="status" value="SOLD OUT" class="peer sr-only">
                    <span class="block rounded-lg border-2 border-cream-200 bg-white px-3 py-2.5 text-center font-bold peer-checked:border-ink peer-checked:bg-ink peer-checked:text-cream-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-teal-500">Sold out</span>
                </label>
            </div>
            @error('status')
                <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
        </fieldset>

        <button type="submit" class="w-full cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]">Save changes</button>
    </form>

    <div class="border-t-2 border-cream-200 px-5 py-4">
        <button type="button" data-confirm-open class="w-full cursor-pointer rounded-lg border-2 border-danger px-4 py-2.5 font-bold text-danger hover:bg-danger hover:text-cream-50">Remove from menu</button>
    </div>
</dialog>

{{-- Save changes confirmation --}}
<dialog
    id="update-confirm-dialog"
    aria-labelledby="update-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="update-confirm-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Save changes?</h2>
        <p class="mt-2 text-ink-soft"><strong class="text-ink" data-update-confirm-name></strong> will be set to <strong class="text-ink" data-update-confirm-price></strong> and marked <strong class="text-ink" data-update-confirm-status></strong> on today's menu.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-update-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">Save Changes</button>
        </div>
    </div>
</dialog>

{{-- Remove confirmation --}}
<dialog
    id="confirm-dialog"
    aria-labelledby="confirm-dialog-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <form method="POST" data-delete-form data-url="{{ route('daily-items.destroy', '__ID__') }}" class="p-5">
        @csrf
        @method('DELETE')
        <h2 id="confirm-dialog-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Remove from menu?</h2>
        <p class="mt-2 text-ink-soft"><strong class="text-ink" data-confirm-name></strong> will no longer appear on today's menu.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" class="cursor-pointer rounded-lg bg-danger px-4 py-2.5 font-bold text-cream-50 hover:brightness-110">Remove</button>
        </div>
    </form>
</dialog>

{{-- Add item modal --}}
<dialog
    id="add-dialog"
    aria-labelledby="add-dialog-title"
    data-reopen="{{ $errors->addItem->any() ? '1' : '' }}"
    data-old-item="{{ old('item_id') }}"
    data-old-price="{{ old('price') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <h2 id="add-dialog-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Add item to menu</h2>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form method="POST" action="{{ route('daily-items.store') }}" data-add-form class="px-5 py-5">
        @csrf

        @if ($addableItems->isEmpty())
            <p class="rounded-lg border-2 border-dashed border-cream-200 px-4 py-6 text-center text-ink-soft">Every item is already on today's menu.</p>
        @else
            <div class="space-y-5">
                <div>
                    <label for="add-item" class="block text-sm font-bold">Item</label>
                    <select
                        id="add-item" name="item_id" required
                        class="mt-1.5 w-full cursor-pointer rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink @error('item_id', 'addItem') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                    >
                        <option value="">Choose an item…</option>
                        {{-- options:start --}}
                        @foreach ($addableItems as $categoryName => $categoryItems)
                            <optgroup label="{{ $categoryName }}">
                                @foreach ($categoryItems as $item)
                                    <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ $item->default_price }}" @selected(old('item_id') == $item->id)>{{ $item->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        {{-- options:end --}}
                    </select>
                    @error('item_id', 'addItem')
                        <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="add-price" class="block text-sm font-bold">Price</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                        <input
                            id="add-price" name="price" type="number" inputmode="decimal" step="0.01" min="0" required
                            class="w-full rounded-lg border-2 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink @error('price', 'addItem') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                        >
                    </div>
                    @error('price', 'addItem')
                        <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        <div class="mt-6 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button
                type="submit"
                @disabled($addableItems->isEmpty())
                class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-snug text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
            >Save</button>
        </div>
    </form>
</dialog>

{{-- Add item confirmation --}}
<dialog
    id="add-confirm-dialog"
    aria-labelledby="add-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="add-confirm-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Add to menu?</h2>
        <p class="mt-2 text-ink-soft"><strong class="text-ink" data-add-confirm-name></strong> will be added to today's menu at <strong class="text-ink" data-add-confirm-price></strong>.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-add-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:opacity-50">Add to menu</button>
        </div>
    </div>
</dialog>

{{-- Bottom action bar --}}
<div class="fixed inset-x-0 bottom-0 z-10 flex h-20 items-center justify-center gap-3 border-t-2 border-cream-200 bg-cream-50 px-4 transition-[left] duration-200 sm:justify-end sm:px-8 lg:group-data-[menu=open]/shell:left-64">
    <button
        type="button"
        data-add-item-open
        class="cursor-pointer rounded-lg border-2 border-teal-700 px-4 py-3 font-display text-base font-bold leading-tight text-teal-700 transition hover:bg-teal-700 hover:text-cream-50 sm:px-6 sm:text-lg"
    >Add Item to Menu</button>
</div>
@endsection
