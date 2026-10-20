@extends('layouts.main')

@section('title', 'Dashboard · ' . config('app.name'))

@section('main')
<div class="pb-28">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4">
        <h1 class="font-display text-3xl font-bold text-teal-900">Today's menu</h1>
        <p class="font-semibold text-ink-soft">{{ now()->format('l, F j') }}</p>
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
                            <li @class([
                                'flex min-h-32 flex-col justify-between gap-3 rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-4',
                                'opacity-60' => $soldOut,
                            ])>
                                <div>
                                    <h2 class="font-display text-lg font-bold leading-snug text-ink">{{ $dailyItem->item->name }}</h2>
                                    @if ($soldOut)
                                        <span class="mt-1 inline-block rounded bg-ink px-2 py-0.5 text-xs font-bold text-cream-50">Sold out</span>
                                    @endif
                                </div>
                                <p class="text-xl font-bold text-teal-700">₱{{ number_format($dailyItem->price, 2) }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Reserved for action buttons --}}
<div class="fixed inset-x-0 bottom-0 z-10 h-20 border-t-2 border-cream-200 bg-cream-50 transition-[left] duration-200 lg:group-data-[menu=open]/shell:left-64"></div>
@endsection
