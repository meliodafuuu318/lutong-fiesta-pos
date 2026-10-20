@extends('layouts.main')

@section('title', 'Items · ' . config('app.name'))

@section('main')
<div class="pb-28">
    <h1 class="font-display text-3xl font-bold text-teal-900">Items</h1>

    @if ($categories->isEmpty())
        <p class="mt-8 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            There are no categories yet.
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
                @php($items = $itemsByCategory[$category->id])
                <div
                    role="tabpanel"
                    id="panel-{{ $category->id }}"
                    aria-labelledby="tab-{{ $category->id }}"
                    tabindex="0"
                    class="mx-auto mt-2 max-w-6xl"
                    @unless ($loop->first) hidden @endunless
                >
                    @if ($items->isEmpty())
                        <p class="rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
                            There are no items in this category yet.
                        </p>
                    @else
                        <div class="overflow-x-auto rounded-xl border-2 border-cream-200 bg-cream-50">
                            <table class="w-full table-fixed text-center">
                                <caption class="sr-only">{{ $category->name }} items</caption>
                                <colgroup>
                                    <col class="w-[34%] sm:w-1/4">
                                    <col class="w-[22%] sm:w-1/4">
                                    <col class="w-[22%] sm:w-1/4">
                                    <col class="w-[22%] sm:w-1/4">
                                </colgroup>
                                <thead class="bg-cream-100 text-sm font-bold uppercase tracking-wide text-ink-soft">
                                    <tr>
                                        <th scope="col" class="px-2 py-3 sm:px-4">Item</th>
                                        <th scope="col" class="px-2 py-3 sm:px-4">Default Price</th>
                                        <th scope="col" class="px-2 py-3 sm:px-4">Active</th>
                                        <th scope="col" class="px-2 py-3 sm:px-4"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60">
                                            <th scope="row" class="px-2 py-3 font-bold text-ink sm:px-4">{{ $item->name }}</th>
                                            <td class="whitespace-nowrap px-2 py-3 font-bold text-teal-700 sm:px-4">₱{{ number_format($item->default_price, 2) }}</td>
                                            <td class="px-2 py-3 sm:px-4">
                                                <button
                                                    type="button"
                                                    role="switch"
                                                    data-item-toggle
                                                    data-url="{{ route('items.toggle-active', $item) }}"
                                                    data-name="{{ $item->name }}"
                                                    aria-checked="{{ $item->is_active ? 'true' : 'false' }}"
                                                    aria-label="{{ $item->name }} is active"
                                                    class="group relative inline-flex h-7 w-12 shrink-0 cursor-pointer items-center rounded-full bg-ink/25 transition-colors disabled:cursor-wait disabled:opacity-60 aria-checked:bg-teal-700"
                                                >
                                                    <span class="absolute left-1 size-5 rounded-full bg-white shadow transition-transform group-aria-checked:translate-x-5"></span>
                                                </button>
                                            </td>
                                            <td class="px-2 py-3 sm:px-4">
                                                <button
                                                    type="button"
                                                    data-item-delete
                                                    data-url="{{ route('items.destroy', $item) }}"
                                                    data-name="{{ $item->name }}"
                                                    aria-label="Delete {{ $item->name }}"
                                                    class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg text-ink-soft hover:bg-danger/10 hover:text-danger sm:size-10"
                                                >
                                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M3 6h18"/>
                                                        <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                        <path d="M10 11v6M14 11v6"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $items->links('pagination.lutong') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Delete confirmation --}}
<dialog
    id="delete-item-dialog"
    aria-labelledby="delete-item-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <form method="POST" data-delete-item-form data-url="{{ route('items.destroy', '__ID__') }}" class="p-5">
        @csrf
        @method('DELETE')
        <h2 id="delete-item-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Delete Item?</h2>
        <p class="mt-2 text-ink-soft"><strong class="text-ink" data-delete-item-name></strong> will be permanently deleted, along with its menu entries. This can't be undone.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" class="cursor-pointer rounded-lg bg-danger px-4 py-2.5 font-bold text-cream-50 hover:brightness-110">Delete</button>
        </div>
    </form>
</dialog>

{{-- Add item modal --}}
<dialog
    id="create-item-dialog"
    aria-labelledby="create-item-title"
    data-reopen="{{ $errors->createItem->any() ? '1' : '' }}"
    data-old-name="{{ old('name') }}"
    data-old-price="{{ old('default_price') }}"
    data-old-category="{{ old('category_id') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <h2 id="create-item-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Add Item</h2>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form method="POST" action="{{ route('items.store') }}" data-create-item-form class="px-5 py-5">
        @csrf

        <div class="space-y-5">
            <div>
                <label for="create-item-name" class="block text-sm font-bold">Item Name</label>
                <input
                    id="create-item-name" name="name" type="text" maxlength="255" required autocomplete="off"
                    class="mt-1.5 w-full rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink @error('name', 'createItem') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                >
                @error('name', 'createItem')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="create-item-price" class="block text-sm font-bold">Default Price</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                    <input
                        id="create-item-price" name="default_price" type="number" inputmode="decimal" step="0.01" min="0" required
                        class="w-full rounded-lg border-2 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink @error('default_price', 'createItem') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                    >
                </div>
                @error('default_price', 'createItem')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="create-item-category" class="block text-sm font-bold">Category</label>
                <select
                    id="create-item-category" name="category_id" required
                    class="mt-1.5 w-full cursor-pointer rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink @error('category_id', 'createItem') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                >
                    <option value="">Choose a category…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id', 'createItem')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-snug text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]">Save Item</button>
        </div>
    </form>
</dialog>

{{-- Add item confirmation --}}
<dialog
    id="create-item-confirm-dialog"
    aria-labelledby="create-item-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="create-item-confirm-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Save Item?</h2>
        <p class="mt-2 text-ink-soft"><strong class="text-ink" data-confirm-item-name></strong> will be added to <strong class="text-ink" data-confirm-item-category></strong> at <strong class="text-ink" data-confirm-item-price></strong>.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-create-item-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:opacity-50">Save Item</button>
        </div>
    </div>
</dialog>

{{-- Bottom action bar --}}
<div class="fixed inset-x-0 bottom-0 z-10 flex h-20 items-center justify-end gap-3 border-t-2 border-cream-200 bg-cream-50 px-4 transition-[left] duration-200 sm:px-8 lg:group-data-[menu=open]/shell:left-64">
    <button
        type="button"
        data-create-item-open
        class="flex-1 cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] sm:flex-none sm:px-6 sm:text-lg"
    >Add Item</button>
</div>
@endsection
