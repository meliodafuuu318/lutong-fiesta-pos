<?php

namespace App\Services;

use App\Models\DailyItem;
use App\Models\Item;
use App\Models\TransactionItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Item-level numbers for the analytics page: which items sell, and how well they sell for the
 * number of days they were actually on the menu.
 *
 * Definitions, so the figures can be explained to whoever reads them:
 *
 * - A "menu appearance" is one daily_items row: the item was on the menu for one day. The date
 *   of that row, not the transaction's date, decides which day a sale belongs to, so a sale and
 *   the appearance it came from always land in the same period.
 * - "Units sold" is the sum of quantities. A line with a null quantity was sold at a custom
 *   price and counts as one unit, the same as the order screens show it.
 * - "Orders" is the number of different transactions that contain the item.
 * - Cancelled and refunded transactions are left out, the same as Transaction::active() does for
 *   the dashboard totals. (Orders that are merely unpaid or undelivered still count as sold.)
 * - "Average per appearance" is units sold / menu appearances. Appearances with no sales count,
 *   which is what makes it a fair comparison between an item that is on the menu every day and
 *   one that only shows up now and then.
 */
class ItemAnalyticsService
{
    public const PRESETS = ['today', 'week', 'month', 'last30', 'all', 'custom'];

    public const SORTS = ['total', 'average', 'revenue'];

    /** Same list as Transaction::scopeActive(); this runs on joins, where the scope can't be used. */
    private const VOID_STATUSES = ['CANCELLED', 'REFUNDED'];

    /**
     * Turn a preset (or a custom from/to) into dates. A null bound means "no limit on that side".
     *
     * @return array{from: ?CarbonImmutable, to: ?CarbonImmutable, label: string}
     */
    public function range(string $preset, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $today = CarbonImmutable::today();

        [$start, $end] = match ($preset) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()->startOfDay()],
            'last30' => [$today->subDays(29), $today],
            'all' => [null, null],
            'custom' => [
                $from ? CarbonImmutable::instance($from)->startOfDay() : null,
                $to ? CarbonImmutable::instance($to)->startOfDay() : null,
            ],
            default => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
        };

        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return ['from' => $start, 'to' => $end, 'label' => $this->label($start, $end)];
    }

    /**
     * Every item that was on the menu in the range, with how much it sold.
     *
     * @return Collection<int, array{
     *     item_id: int, name: string, category: ?string, is_active: bool,
     *     appearances: int, units: int, orders: int, revenue: float, average: float,
     *     rank_total: int, rank_average: int
     * }>
     */
    public function popularity(?CarbonInterface $from, ?CarbonInterface $to, string $sort = 'total', int $minAppearances = 1): Collection
    {
        $appearances = DailyItem::query()
            ->when($from, fn ($query) => $query->where('date', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->where('date', '<', $to->copy()->addDay()->toDateString()))
            ->selectRaw('item_id, COUNT(*) as appearances')
            ->groupBy('item_id')
            ->pluck('appearances', 'item_id');

        if ($appearances->isEmpty()) {
            return collect();
        }

        $sales = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('daily_items', 'daily_items.id', '=', 'transaction_items.daily_item_id')
            ->whereNotIn('transactions.status', self::VOID_STATUSES)
            ->when($from, fn ($query) => $query->where('daily_items.date', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->where('daily_items.date', '<', $to->copy()->addDay()->toDateString()))
            ->groupBy('daily_items.item_id')
            ->selectRaw('daily_items.item_id as item_id')
            ->selectRaw('SUM(COALESCE(transaction_items.quantity, 1)) as units')
            ->selectRaw('COUNT(DISTINCT transaction_items.transaction_id) as orders')
            ->selectRaw('SUM(transaction_items.subtotal) as revenue')
            ->get()
            ->keyBy('item_id');

        $rows = Item::with('category')
            ->whereIn('id', $appearances->keys())
            ->get()
            ->map(function (Item $item) use ($appearances, $sales) {
                $sold = $sales->get($item->id);
                $days = (int) $appearances[$item->id];
                $units = (int) ($sold->units ?? 0);

                return [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category?->name,
                    'is_active' => (bool) $item->is_active,
                    'appearances' => $days,
                    'units' => $units,
                    'orders' => (int) ($sold->orders ?? 0),
                    'revenue' => (float) ($sold->revenue ?? 0),
                    'average' => $days > 0 ? round($units / $days, 4) : 0.0,
                ];
            })
            ->filter(fn (array $row) => $row['appearances'] >= max(1, $minAppearances))
            ->values();

        // Rank before sorting, so both ranks show whichever column the list is sorted by.
        $rankTotal = $this->ranks($rows, 'units');
        $rankAverage = $this->ranks($rows, 'average');

        $primary = match ($sort) {
            'average' => 'average',
            'revenue' => 'revenue',
            default => 'units',
        };
        $secondary = $primary === 'units' ? 'average' : 'units';

        return $rows
            ->map(fn (array $row) => $row + [
                'rank_total' => $rankTotal[$row['item_id']],
                'rank_average' => $rankAverage[$row['item_id']],
            ])
            ->sort(fn (array $a, array $b) => [$b[$primary], $b[$secondary], $a['name']] <=> [$a[$primary], $a[$secondary], $b['name']])
            ->values();
    }

    /**
     * One day's menu with what each item sold. Items that were on the menu but didn't sell are
     * included with zeros, so "what sold" and "what didn't" come from one list.
     *
     * @return array{
     *     rows: Collection<int, array{
     *         daily_item_id: int, name: string, category: ?string, price: float, status: string,
     *         units: int, orders: int, revenue: float
     *     }>,
     *     totals: array{units: int, orders: int, revenue: float, items_sold: int, items_on_menu: int}
     * }
     */
    public function dailyReport(CarbonInterface $date): array
    {
        $menu = DailyItem::query()
            ->onDate($date)
            ->with('item.category')
            ->get();

        $ids = $menu->pluck('id');

        $sales = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereIn('transaction_items.daily_item_id', $ids)
            ->whereNotIn('transactions.status', self::VOID_STATUSES)
            ->groupBy('transaction_items.daily_item_id')
            ->selectRaw('transaction_items.daily_item_id as daily_item_id')
            ->selectRaw('SUM(COALESCE(transaction_items.quantity, 1)) as units')
            ->selectRaw('COUNT(DISTINCT transaction_items.transaction_id) as orders')
            ->selectRaw('SUM(transaction_items.subtotal) as revenue')
            ->get()
            ->keyBy('daily_item_id');

        // An order with three different items is still one order, so this can't be summed from the rows.
        $orderCount = (int) TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereIn('transaction_items.daily_item_id', $ids)
            ->whereNotIn('transactions.status', self::VOID_STATUSES)
            ->distinct()
            ->count('transaction_items.transaction_id');

        $rows = $menu
            ->map(function (DailyItem $dailyItem) use ($sales) {
                $sold = $sales->get($dailyItem->id);

                return [
                    'daily_item_id' => $dailyItem->id,
                    'name' => $dailyItem->item->name,
                    'category' => $dailyItem->item->category?->name,
                    'price' => (float) $dailyItem->price,
                    'status' => $dailyItem->status,
                    'units' => (int) ($sold->units ?? 0),
                    'orders' => (int) ($sold->orders ?? 0),
                    'revenue' => (float) ($sold->revenue ?? 0),
                ];
            })
            ->sort(fn (array $a, array $b) => [$b['units'], $a['name']] <=> [$a['units'], $b['name']])
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'units' => $rows->sum('units'),
                'orders' => $orderCount,
                'revenue' => (float) $rows->sum('revenue'),
                'items_sold' => $rows->where('units', '>', 0)->count(),
                'items_on_menu' => $rows->count(),
            ],
        ];
    }

    /**
     * Competition ranking: equal values share a rank and the next one skips (1, 2, 2, 4).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, int> item_id => rank
     */
    private function ranks(Collection $rows, string $key): array
    {
        $ranks = [];
        $previous = null;
        $rank = 0;

        foreach ($rows->sortByDesc($key)->values() as $position => $row) {
            if ($previous === null || $row[$key] !== $previous) {
                $rank = $position + 1;
                $previous = $row[$key];
            }

            $ranks[$row['item_id']] = $rank;
        }

        return $ranks;
    }

    private function label(?CarbonInterface $from, ?CarbonInterface $to): string
    {
        return match (true) {
            ! $from && ! $to => 'All time',
            ! $from => 'Up to ' . $to->format('M j, Y'),
            ! $to => 'From ' . $from->format('M j, Y'),
            $from->isSameDay($to) => $from->format('M j, Y'),
            default => $from->format('M j') . ' – ' . $to->format('M j, Y'),
        };
    }
}
