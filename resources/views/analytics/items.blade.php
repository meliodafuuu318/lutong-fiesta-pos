@extends('layouts.main')

@section('title', 'Item Analytics · ' . config('app.name'))

@section('main')
@php
    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);
    $tabClasses = 'shrink-0 cursor-pointer rounded-full border-2 border-cream-200 bg-cream-50 px-5 py-2 font-bold text-ink hover:border-teal-500 aria-selected:border-teal-700 aria-selected:bg-teal-700 aria-selected:text-cream-50';
    $fieldClasses = 'mt-1.5 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2.5 font-semibold text-ink focus:border-teal-500';
    $buttonClasses = 'cursor-pointer rounded-lg bg-amber-500 px-5 py-2.5 text-center font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]';
    $number = fn ($value) => number_format((float) $value, 1);

    $presetLabels = [
        'today' => 'Today',
        'week' => 'This week',
        'month' => 'This month',
        'last30' => 'Last 30 days',
        'all' => 'All time',
        'custom' => 'Custom range',
    ];

    // The state of the other tab travels along in hidden fields, so changing one filter doesn't reset the other tab.
    $popularityState = ['range' => $preset, 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'sort' => $sort, 'min_appearances' => $minAppearances];

    $sortLink = fn (string $key) => request()->fullUrlWithQuery(['sort' => $key]) . '#tab-popularity';

    // Summary figures. Ties share the top spot, so show the first one and say how many more.
    $totalUnits = $popularity->sum('units');
    $totalRevenue = $popularity->sum('revenue');
    $topByTotal = $popularity->where('rank_total', 1)->where('units', '>', 0)->sortBy('name')->values();
    $topByAverage = $popularity->where('rank_average', 1)->where('units', '>', 0)->sortBy('name')->values();
    $maxUnits = max(1, (int) $popularity->max('units'));
    $maxAverage = max(0.0001, (float) $popularity->max('average'));

    $dayParam = fn ($date) => request()->fullUrlWithQuery(['date' => $date->format('Y-m-d')]) . '#tab-daily';
    $today = \Carbon\CarbonImmutable::today();
@endphp

<div class="pb-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Item Analytics</h1>
        <p class="font-semibold text-ink-soft">{{ now()->format('l, F j, Y') }}</p>
    </div>

    <div data-tabs class="mt-2">
        <div
            role="tablist"
            aria-label="Item analytics sections"
            class="sticky top-16 z-10 -mx-4 flex gap-2 overflow-x-auto bg-cream-100 px-4 py-3 sm:-mx-8 sm:px-8"
        >
            <button type="button" role="tab" id="tab-popularity" aria-controls="panel-popularity" aria-selected="true" tabindex="0" class="{{ $tabClasses }}">Popularity</button>
            <button type="button" role="tab" id="tab-daily" aria-controls="panel-daily" aria-selected="false" tabindex="-1" class="{{ $tabClasses }}">Daily Item Report</button>
        </div>

        {{-- ============ Popularity ============ --}}
        <div role="tabpanel" id="panel-popularity" aria-labelledby="tab-popularity" tabindex="0" class="mt-2">
            <form method="GET" action="{{ route('analytics.items') }}#tab-popularity" class="rounded-xl border-2 border-cream-200 bg-cream-50 p-4">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="date" value="{{ $day->format('Y-m-d') }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="range" class="block text-sm font-bold">Period</label>
                        <select id="range" name="range" class="{{ $fieldClasses }}">
                            @foreach ($presetLabels as $value => $label)
                                <option value="{{ $value }}" @selected($preset === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-bold">From <span class="font-semibold text-ink-soft">(custom)</span></label>
                        <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}" class="{{ $fieldClasses }}">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-bold">To <span class="font-semibold text-ink-soft">(custom)</span></label>
                        <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}" class="{{ $fieldClasses }}">
                    </div>
                    <div>
                        <label for="min_appearances" class="block text-sm font-bold">Min. menu days</label>
                        <input id="min_appearances" name="min_appearances" type="number" min="1" max="365" value="{{ $minAppearances }}" class="{{ $fieldClasses }}">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="{{ $buttonClasses }} w-full">Apply</button>
                    </div>
                </div>
                <p class="mt-3 text-xs font-semibold text-ink-soft">The From and To dates are only used when the period is Custom range. Leave one empty for no limit on that side.</p>
            </form>

            <p class="mt-4 font-display text-lg font-bold text-teal-900">{{ $range['label'] }}</p>

            @if ($popularity->isEmpty())
                <p class="mt-3 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
                    No items were on the menu in this period{{ $minAppearances > 1 ? ' for at least ' . $minAppearances . ' days' : '' }}.
                </p>
            @else
                <dl class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-5">
                    <div class="rounded-xl border-2 border-t-4 border-cream-200 border-t-teal-600 bg-cream-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Items on menu</dt>
                        <dd class="mt-0.5 font-display text-xl font-bold text-teal-900">{{ $popularity->count() }}</dd>
                    </div>
                    <div class="rounded-xl border-2 border-t-4 border-cream-200 border-t-teal-600 bg-cream-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Units sold</dt>
                        <dd class="mt-0.5 font-display text-xl font-bold text-teal-900">{{ number_format($totalUnits) }}</dd>
                    </div>
                    <div class="rounded-xl border-2 border-t-4 border-cream-200 border-t-teal-600 bg-cream-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Item revenue</dt>
                        <dd class="mt-0.5 break-words font-display text-xl font-bold text-teal-900">{{ $peso($totalRevenue) }}</dd>
                    </div>
                    <div class="col-span-1 rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Best seller (total)</dt>
                        <dd class="mt-0.5 break-words font-display text-lg font-bold text-teal-900">
                            {{ $topByTotal->first()['name'] ?? '—' }}
                            @if ($topByTotal->count() > 1)<span class="text-sm text-ink-soft">+{{ $topByTotal->count() - 1 }} tied</span>@endif
                        </dd>
                    </div>
                    <div class="col-span-1 rounded-xl border-2 border-t-4 border-cream-200 border-t-amber-500 bg-cream-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-ink-soft">Best per menu day</dt>
                        <dd class="mt-0.5 break-words font-display text-lg font-bold text-teal-900">
                            {{ $topByAverage->first()['name'] ?? '—' }}
                            @if ($topByAverage->count() > 1)<span class="text-sm text-ink-soft">+{{ $topByAverage->count() - 1 }} tied</span>@endif
                        </dd>
                    </div>
                </dl>

                <section class="mt-4 rounded-xl border-2 border-cream-200 bg-cream-50" aria-labelledby="popularity-title">
                    <div class="border-b-2 border-cream-200 px-5 py-3">
                        <h2 id="popularity-title" class="font-display text-xl font-bold text-teal-900">Item popularity</h2>
                        <p class="mt-0.5 text-sm font-semibold text-ink-soft">Total sold is how many units went out. Average per menu day is units sold divided by the days the item was on the menu, so an item that was only offered a few times isn't out-ranked just for being offered less.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[44rem] text-right">
                            <caption class="sr-only">Items ranked by units sold and by average units sold per menu day</caption>
                            <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left">Item</th>
                                    <th scope="col" class="px-4 py-3">Menu days</th>
                                    <th scope="col" class="px-4 py-3" @if ($sort === 'total') aria-sort="descending" @endif>
                                        <a href="{{ $sortLink('total') }}" @class(['underline underline-offset-4', 'text-teal-700' => $sort === 'total'])>Total sold</a>
                                    </th>
                                    <th scope="col" class="px-4 py-3" @if ($sort === 'average') aria-sort="descending" @endif>
                                        <a href="{{ $sortLink('average') }}" @class(['underline underline-offset-4', 'text-teal-700' => $sort === 'average'])>Avg per menu day</a>
                                    </th>
                                    <th scope="col" class="px-4 py-3">Orders</th>
                                    <th scope="col" class="px-4 py-3" @if ($sort === 'revenue') aria-sort="descending" @endif>
                                        <a href="{{ $sortLink('revenue') }}" @class(['underline underline-offset-4', 'text-teal-700' => $sort === 'revenue'])>Revenue</a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($popularity as $row)
                                    <tr class="border-t-2 border-cream-200">
                                        <th scope="row" class="px-4 py-3 text-left font-bold text-ink">
                                            {{ $row['name'] }}
                                            @unless ($row['is_active'])<span class="ml-1 rounded bg-ink/10 px-1.5 py-0.5 align-middle text-xs font-bold text-ink-soft">Inactive</span>@endunless
                                            @if ($row['category'])<span class="block text-xs font-semibold text-ink-soft">{{ $row['category'] }}</span>@endif
                                        </th>
                                        <td class="px-4 py-3 font-semibold text-ink">{{ $row['appearances'] }}</td>
                                        <td class="px-4 py-3 font-bold text-ink">
                                            {{ number_format($row['units']) }}
                                            <span class="ml-1 rounded-full bg-teal-100 px-2 py-0.5 align-middle text-xs font-bold text-teal-900" title="Rank by total sold">#{{ $row['rank_total'] }}</span>
                                            <span class="mt-1 ml-auto block h-1.5 w-24 rounded-full bg-cream-200" aria-hidden="true"><span class="block h-full rounded-full bg-amber-500" style="width: {{ round($row['units'] / $maxUnits * 100) }}%"></span></span>
                                        </td>
                                        <td class="px-4 py-3 font-bold text-ink">
                                            {{ $number($row['average']) }}
                                            <span class="ml-1 rounded-full bg-teal-100 px-2 py-0.5 align-middle text-xs font-bold text-teal-900" title="Rank by average per menu day">#{{ $row['rank_average'] }}</span>
                                            <span class="mt-1 ml-auto block h-1.5 w-24 rounded-full bg-cream-200" aria-hidden="true"><span class="block h-full rounded-full bg-teal-700" style="width: {{ round($row['average'] / $maxAverage * 100) }}%"></span></span>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-ink">{{ number_format($row['orders']) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-ink">{{ $peso($row['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <p class="mt-2 text-xs font-semibold text-ink-soft">Sales are counted on the day the item was on the menu. Cancelled and refunded orders are left out; unpaid and undelivered orders are included. A line sold at a custom price (no quantity) counts as one unit.</p>
            @endif
        </div>

        {{-- ============ Daily item report ============ --}}
        <div role="tabpanel" id="panel-daily" aria-labelledby="tab-daily" tabindex="0" class="mt-2" hidden>
            <div class="flex flex-wrap items-end justify-between gap-3 rounded-xl border-2 border-cream-200 bg-cream-50 p-4">
                <form method="GET" action="{{ route('analytics.items') }}#tab-daily" class="flex flex-wrap items-end gap-3">
                    @foreach ($popularityState as $name => $value)
                        @if ($value !== null && $value !== '')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <div>
                        <label for="date" class="block text-sm font-bold">Day</label>
                        <input id="date" name="date" type="date" value="{{ $day->format('Y-m-d') }}" class="{{ $fieldClasses }}">
                    </div>
                    <button type="submit" class="{{ $buttonClasses }}">Show</button>
                </form>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $dayParam($day->subDay()) }}" class="rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold text-ink hover:border-teal-500">&larr; Previous day</a>
                    @unless ($day->isSameDay($today))
                        <a href="{{ $dayParam($today) }}" class="rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold text-ink hover:border-teal-500">Today</a>
                    @endunless
                    <a href="{{ $dayParam($day->addDay()) }}" class="rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold text-ink hover:border-teal-500">Next day &rarr;</a>
                </div>
            </div>

            <section class="mt-4 rounded-xl border-2 border-cream-200 bg-cream-50" aria-labelledby="daily-title">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 border-b-2 border-cream-200 px-5 py-3">
                    <h2 id="daily-title" class="font-display text-xl font-bold text-teal-900">
                        {{ $day->format('l, F j, Y') }}
                        @if ($day->isSameDay($today))<span class="ml-1 rounded bg-teal-700 px-1.5 py-0.5 align-middle text-xs font-bold text-cream-50">Today</span>@endif
                    </h2>
                    <p class="text-sm font-semibold text-ink-soft">{{ $daily['totals']['items_sold'] }} of {{ $daily['totals']['items_on_menu'] }} menu items sold</p>
                </div>

                @if ($daily['rows']->isEmpty())
                    <p class="px-6 py-12 text-center text-ink-soft">Nothing was on the menu this day.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[36rem] text-right">
                            <caption class="sr-only">Units sold, orders and revenue for each menu item on {{ $day->format('F j, Y') }}</caption>
                            <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft sm:text-sm">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left">Item</th>
                                    <th scope="col" class="px-4 py-3">Price</th>
                                    <th scope="col" class="px-4 py-3">Sold</th>
                                    <th scope="col" class="px-4 py-3">Orders</th>
                                    <th scope="col" class="px-4 py-3">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daily['rows'] as $row)
                                    <tr @class(['border-t-2 border-cream-200', 'text-ink-soft' => $row['units'] === 0])>
                                        <th scope="row" class="px-4 py-3 text-left font-bold {{ $row['units'] === 0 ? 'text-ink-soft' : 'text-ink' }}">
                                            {{ $row['name'] }}
                                            @if ($row['status'] === 'SOLD OUT')<span class="ml-1 rounded bg-danger/10 px-1.5 py-0.5 align-middle text-xs font-bold text-danger">Sold out</span>@endif
                                            @if ($row['category'])<span class="block text-xs font-semibold text-ink-soft">{{ $row['category'] }}</span>@endif
                                        </th>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $peso($row['price']) }}</td>
                                        <td @class(['px-4 py-3 font-bold', 'text-ink' => $row['units'] > 0])>{{ number_format($row['units']) }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ number_format($row['orders']) }}</td>
                                        <td @class(['whitespace-nowrap px-4 py-3 font-bold', 'text-ink' => $row['units'] > 0])>{{ $peso($row['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-cream-200 bg-cream-100">
                                    <th scope="row" class="px-4 py-3 text-left font-bold text-ink">Total</th>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3 font-bold text-ink">{{ number_format($daily['totals']['units']) }}</td>
                                    <td class="px-4 py-3 font-bold text-ink" title="Different orders that include at least one of these items">{{ number_format($daily['totals']['orders']) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-ink">{{ $peso($daily['totals']['revenue']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>
            <p class="mt-2 text-xs font-semibold text-ink-soft">Sold is units; Orders is how many different orders included the item. Items that were on the menu but didn't sell are listed with zeros. Cancelled and refunded orders are left out.</p>
        </div>
    </div>
</div>
@endsection
