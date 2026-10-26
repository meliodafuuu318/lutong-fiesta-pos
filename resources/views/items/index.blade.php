@extends('layouts.main')

@section('title', 'Items · ' . config('app.name'))

@section('main')
<div class="w-full">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Items</h1>
        <button
            type="button"
            data-item-add-open
            class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
        >Add Item</button>
    </div>

    <form method="GET" action="{{ route('items.index') }}" class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50 p-4">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
            <div class="col-span-2">
                <label for="items-search" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Search</label>
                <input
                    id="items-search"
                    name="search"
                    type="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search item name..."
                    class="mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500"
                >
            </div>
            <div>
                <label for="items-category" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Category</label>
                <select id="items-category" name="category" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="ALL" @selected(($categoryFilter ?? 'ALL') === 'ALL')>All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->name }}" @selected(($categoryFilter ?? 'ALL') === $category->name)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="items-status" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Status</label>
                <select id="items-status" name="status" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="ALL" @selected(($statusFilter ?? 'ALL') === 'ALL')>All</option>
                    <option value="ACTIVE" @selected(($statusFilter ?? 'ALL') === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected(($statusFilter ?? 'ALL') === 'INACTIVE')>Inactive</option>
                </select>
            </div>
            <div>
                <label for="items-sort-by" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Sort By</label>
                <select id="items-sort-by" name="sort_by" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="name" @selected(($sortBy ?? 'name') === 'name')>Name</option>
                    <option value="default_price" @selected(($sortBy ?? 'name') === 'default_price')>Price</option>
                    <option value="created_at" @selected(($sortBy ?? 'name') === 'created_at')>Created</option>
                    <option value="id" @selected(($sortBy ?? 'name') === 'id')>ID</option>
                </select>
            </div>
            <div>
                <label for="items-sort-order" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Order</label>
                <select id="items-sort-order" name="sort_order" class="mt-1 w-full cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500">
                    <option value="asc" @selected(($sortOrder ?? 'asc') === 'asc')>Ascending</option>
                    <option value="desc" @selected(($sortOrder ?? 'asc') === 'desc')>Descending</option>
                </select>
            </div>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:flex sm:items-center sm:justify-end">
            <a href="{{ route('items.index') }}" class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 text-center text-sm font-bold hover:border-teal-500 sm:py-2">Reset</a>
            <button type="submit" class="cursor-pointer rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-bold text-cream-50 hover:bg-teal-600 sm:py-2">Apply</button>
        </div>
    </form>

    @php($itemsList = $items ?? null)
    @if ($itemsList === null || $itemsList->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            No items found.
        </p>
    @else
        <div class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50 max-lg:border-0 max-lg:bg-transparent">
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-center max-lg:block lg:min-w-[44rem]">
                    <caption class="sr-only">Items</caption>
                    <thead class="bg-cream-100 max-lg:hidden text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                        <tr>
                            <th scope="col" class="px-6 py-3">Item</th>
                            <th scope="col" class="px-6 py-3">Category</th>
                            <th scope="col" class="px-6 py-3">Default Price</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3"><span class="sr-only">Edit</span></th>
                            <th scope="col" class="px-6 py-3"><span class="sr-only">Delete</span></th>
                        </tr>
                    </thead>
                    <tbody class="max-lg:block max-lg:space-y-3">
                        @foreach ($itemsList as $item)
                            <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60 max-lg:grid max-lg:grid-cols-2 max-lg:gap-x-2 max-lg:rounded-xl max-lg:border-2 max-lg:bg-cream-50 max-lg:p-4">
                                <th scope="row" class="px-6 py-3 text-center font-bold text-ink max-lg:col-span-2 max-lg:block max-lg:p-0 max-lg:pb-2 max-lg:text-left max-lg:font-display max-lg:text-lg">{{ $item->name }}</th>
                                <td data-label="Category" class="px-6 py-3 font-semibold text-ink max-lg:col-span-2 max-lg:flex max-lg:items-center max-lg:justify-between max-lg:gap-3 max-lg:border-t max-lg:border-cream-200 max-lg:px-0 max-lg:py-2 max-lg:before:text-xs max-lg:before:font-bold max-lg:before:uppercase max-lg:before:tracking-wide max-lg:before:text-ink-soft max-lg:before:content-[attr(data-label)]">{{ $item->category?->name ?? '—' }}</td>
                                <td data-label="Default Price" class="whitespace-nowrap px-6 py-3 font-bold text-teal-700 max-lg:col-span-2 max-lg:flex max-lg:items-center max-lg:justify-between max-lg:gap-3 max-lg:border-t max-lg:border-cream-200 max-lg:px-0 max-lg:py-2 max-lg:before:text-xs max-lg:before:font-bold max-lg:before:uppercase max-lg:before:tracking-wide max-lg:before:text-ink-soft max-lg:before:content-[attr(data-label)]">₱{{ number_format($item->default_price, 2) }}</td>
                                <td data-label="Status" class="px-6 py-3 max-lg:col-span-2 max-lg:flex max-lg:items-center max-lg:justify-between max-lg:gap-3 max-lg:border-t max-lg:border-cream-200 max-lg:px-0 max-lg:py-2 max-lg:before:text-xs max-lg:before:font-bold max-lg:before:uppercase max-lg:before:tracking-wide max-lg:before:text-ink-soft max-lg:before:content-[attr(data-label)]">
                                    <button
                                        type="button"
                                        role="switch"
                                        data-item-toggle
                                        data-url="{{ route('items.toggle-active', $item) }}"
                                        data-name="{{ $item->name }}"
                                        aria-checked="{{ $item->is_active ? 'true' : 'false' }}"
                                        aria-label="{{ $item->name }} is {{ $item->is_active ? 'active' : 'inactive' }}"
                                        class="group relative inline-flex h-7 w-12 shrink-0 cursor-pointer items-center rounded-full bg-ink/25 transition-colors disabled:cursor-wait disabled:opacity-60 aria-checked:bg-teal-700"
                                    >
                                        <span class="absolute left-1 size-5 rounded-full bg-white shadow transition-transform group-aria-checked:translate-x-5"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-3 max-lg:border-t max-lg:border-cream-200 max-lg:px-0 max-lg:pb-0 max-lg:pt-3">
                                    <button
                                        type="button"
                                        data-item-edit
                                        data-id="{{ $item->id }}"
                                        data-url="{{ route('items.update', $item) }}"
                                        data-name="{{ $item->name }}"
                                        data-price="{{ $item->default_price }}"
                                        data-category="{{ $item->category_id }}"
                                        aria-label="Edit {{ $item->name }}"
                                        class="inline-flex size-10 cursor-pointer items-center justify-center gap-2 rounded-lg text-ink-soft hover:bg-teal-100 hover:text-teal-700 max-lg:h-11 max-lg:w-full max-lg:border-2 max-lg:border-cream-200 max-lg:bg-white max-lg:font-bold max-lg:text-ink"
                                    >
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                        <span class="lg:hidden" aria-hidden="true">Edit</span>
                                    </button>
                                </td>
                                <td class="px-6 py-3 max-lg:border-t max-lg:border-cream-200 max-lg:px-0 max-lg:pb-0 max-lg:pt-3">
                                    <button
                                        type="button"
                                        data-item-delete
                                        data-url="{{ route('items.destroy', $item) }}"
                                        data-name="{{ $item->name }}"
                                        aria-label="Delete {{ $item->name }}"
                                        class="inline-flex size-10 cursor-pointer items-center justify-center gap-2 rounded-lg text-ink-soft hover:bg-danger/10 hover:text-danger max-lg:h-11 max-lg:w-full max-lg:border-2 max-lg:border-danger/40 max-lg:bg-white max-lg:font-bold max-lg:text-danger"
                                    >
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                            <path d="M10 11v6M14 11v6"/>
                                        </svg>
                                        <span class="lg:hidden" aria-hidden="true">Delete</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $itemsList->links('pagination.lutong') }}
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

{{-- Add / edit item modal --}}
<dialog
    id="item-form-dialog"
    aria-labelledby="item-form-title"
    data-reopen="{{ $errors->itemForm->any() ? '1' : '' }}"
    data-old-item-id="{{ old('item_id') }}"
    data-old-name="{{ old('name') }}"
    data-old-price="{{ old('default_price') }}"
    data-old-category="{{ old('category_id') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <h2 id="item-form-title" data-form-title class="font-display text-2xl font-bold leading-tight text-teal-900">Add Item</h2>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('items.store') }}"
        data-item-form
        data-store-url="{{ route('items.store') }}"
        class="px-5 py-5"
    >
        @csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        <input type="hidden" name="item_id" value="">

        <div class="space-y-5">
            <div>
                <label for="item-form-name" class="block text-sm font-bold">Item Name</label>
                <input
                    id="item-form-name" name="name" type="text" maxlength="255" required autocomplete="off"
                    class="mt-1.5 w-full rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink read-only:cursor-not-allowed read-only:border-cream-200 read-only:bg-cream-100 read-only:text-ink-soft @error('name', 'itemForm') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                >
                @error('name', 'itemForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="item-form-price" class="block text-sm font-bold">Default Price</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                    <input
                        id="item-form-price" name="default_price" type="number" inputmode="decimal" step="0.01" min="0" required
                        class="w-full rounded-lg border-2 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink @error('default_price', 'itemForm') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                    >
                </div>
                @error('default_price', 'itemForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="item-form-category" class="block text-sm font-bold">Category</label>
                <select
                    id="item-form-category" name="category_id" required
                    class="mt-1.5 w-full cursor-pointer rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink @error('category_id', 'itemForm') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                >
                    <option value="">Choose a category…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id', 'itemForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" data-form-submit class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-snug text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]">Save Item</button>
        </div>
    </form>
</dialog>

{{-- Add / edit item confirmation --}}
<dialog
    id="item-form-confirm-dialog"
    aria-labelledby="item-form-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="item-form-confirm-title" data-confirm-title class="font-display text-2xl font-bold leading-tight text-teal-900">Save Item?</h2>
        <p class="mt-2 text-ink-soft"><strong class="text-ink" data-confirm-name></strong> will be <span data-confirm-verb>added to</span> <strong class="text-ink" data-confirm-category></strong> at <strong class="text-ink" data-confirm-price></strong>.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-item-form-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:opacity-50">Save Item</button>
        </div>
    </div>
</dialog>
@endsection
