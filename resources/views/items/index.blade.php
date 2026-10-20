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
                class="sticky top-16 z-10 -mx-4 flex gap-2 overflow-x-auto bg-cream-100 px-2 py-3 sm:px-4 sm:-mx-8 sm:px-8"
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
                    class="mt-2"
                    @unless ($loop->first) hidden @endunless
                >
                    @if ($items->isEmpty())
                        <p class="rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
                            There are no items in this category yet.
                        </p>
                    @else
                        <div class="overflow-x-auto rounded-xl border-2 border-cream-200 bg-cream-50">
                            <table class="w-full text-left">
                                <caption class="sr-only">{{ $category->name }} items</caption>
                                <thead class="bg-cream-100 text-sm font-bold uppercase tracking-wide text-ink-soft">
                                    <tr>
                                        <th scope="col" class="px-2 py-3 sm:px-4">Item</th>
                                        <th scope="col" class="px-2 py-3 sm:px-4">Active</th>
                                        <th scope="col" class="px-2 py-3 sm:px-4 text-right">Default Price</th>
                                        <th scope="col" class="w-px px-2 py-3 sm:px-4"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60">
                                            <th scope="row" class="px-2 py-3 sm:px-4 font-bold text-ink">{{ $item->name }}</th>
                                            <td class="px-2 py-3 sm:px-4">
                                                {{-- Display only for now --}}
                                                <button
                                                    type="button"
                                                    role="switch"
                                                    aria-checked="{{ $item->is_active ? 'true' : 'false' }}"
                                                    aria-label="{{ $item->name }} is active"
                                                    class="group relative inline-flex h-7 w-12 shrink-0 cursor-pointer items-center rounded-full bg-ink/25 transition-colors aria-checked:bg-teal-700"
                                                >
                                                    <span class="absolute left-1 size-5 rounded-full bg-white shadow transition-transform group-aria-checked:translate-x-5"></span>
                                                </button>
                                            </td>
                                            <td class="whitespace-nowrap px-2 py-3 sm:px-4 text-right font-bold text-teal-700">₱{{ number_format($item->default_price, 2) }}</td>
                                            <td class="px-2 py-3 sm:px-4 text-right">
                                                {{-- Not functional yet --}}
                                                <button
                                                    type="button"
                                                    aria-label="Delete {{ $item->name }}"
                                                    class="inline-flex size-9 cursor-pointer sm:size-10 items-center justify-center rounded-lg text-ink-soft hover:bg-danger/10 hover:text-danger"
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

{{-- Bottom action bar --}}
<div class="fixed inset-x-0 bottom-0 z-10 flex h-20 items-center justify-end gap-3 border-t-2 border-cream-200 bg-cream-50 px-4 transition-[left] duration-200 sm:px-8 lg:group-data-[menu=open]/shell:left-64">
    {{-- Not functional yet --}}
    <button
        type="button"
        class="flex-1 cursor-pointer rounded-lg bg-amber-500 px-2 py-3 sm:px-4 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] sm:flex-none sm:px-6 sm:text-lg"
    >Add Item</button>
</div>
@endsection
