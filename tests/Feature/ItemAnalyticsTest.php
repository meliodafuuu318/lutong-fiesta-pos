<?php

use App\Models\DailyItem;
use App\Models\Item;
use App\Models\User;
use App\Services\ItemAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

/** Put an item on the menu for a day. Passing an existing item gives it another appearance. */
function analyticsMenu(string|Item $item, string $date, float $price = 100.0): DailyItem
{
    if ($item instanceof Item) {
        return DailyItem::create(['item_id' => $item->id, 'price' => $price, 'date' => $date, 'status' => 'AVAILABLE']);
    }

    return BackupData::menuItem($item, $price, $date);
}

/**
 * One order made of [DailyItem, quantity, unit price] lines.
 *
 * @param  array<int, array{0: DailyItem, 1: ?int, 2?: float}>  $lines
 * @param  array<string, mixed>  $attributes
 */
function analyticsOrder(array $lines, array $attributes = [])
{
    $transaction = BackupData::transaction($attributes);

    foreach ($lines as $line) {
        BackupData::line($transaction, $line[0], $line[1], $line[2] ?? 100.0);
    }

    return $transaction;
}

function analyticsRow($rows, string $name): array
{
    return $rows->firstWhere('name', $name);
}

it('ranks items by total sold and by average per menu appearance, which can disagree', function () {
    // Daily: on the menu two days, sold 3 + 1. Special: on the menu one day, sold 3.
    $daily = analyticsMenu('Daily Adobo', '2026-10-01');
    $dailyAgain = analyticsMenu($daily->item, '2026-10-02');
    $special = analyticsMenu('Special Lechon', '2026-10-01');

    analyticsOrder([[$daily, 3], [$special, 3]]);
    analyticsOrder([[$dailyAgain, 1]]);

    $byTotal = app(ItemAnalyticsService::class)->popularity(null, null, 'total');

    expect($byTotal->pluck('name')->all())->toBe(['Daily Adobo', 'Special Lechon'])
        ->and(analyticsRow($byTotal, 'Daily Adobo'))->toMatchArray(['units' => 4, 'appearances' => 2, 'average' => 2.0, 'rank_total' => 1, 'rank_average' => 2])
        ->and(analyticsRow($byTotal, 'Special Lechon'))->toMatchArray(['units' => 3, 'appearances' => 1, 'average' => 3.0, 'rank_total' => 2, 'rank_average' => 1]);

    $byAverage = app(ItemAnalyticsService::class)->popularity(null, null, 'average');

    expect($byAverage->pluck('name')->all())->toBe(['Special Lechon', 'Daily Adobo']);
});

it('counts menu days with no sales against an item\'s average', function () {
    $item = analyticsMenu('Sinigang', '2026-10-01');
    foreach (['2026-10-02', '2026-10-03', '2026-10-04'] as $date) {
        analyticsMenu($item->item, $date);
    }

    analyticsOrder([[$item, 2]]);

    $row = analyticsRow(app(ItemAnalyticsService::class)->popularity(null, null), 'Sinigang');

    expect($row)->toMatchArray(['appearances' => 4, 'units' => 2, 'average' => 0.5]);
});

it('lists an item that was on the menu but never sold, with zeros', function () {
    analyticsMenu('Kare-kare', '2026-10-01');

    $row = analyticsRow(app(ItemAnalyticsService::class)->popularity(null, null), 'Kare-kare');

    expect($row)->toMatchArray(['appearances' => 1, 'units' => 0, 'orders' => 0, 'revenue' => 0.0, 'average' => 0.0]);
});

it('leaves cancelled and refunded orders out but keeps unpaid ones', function () {
    $item = analyticsMenu('Pancit', '2026-10-01');

    analyticsOrder([[$item, 2]]);
    analyticsOrder([[$item, 5]], ['status' => 'CANCELLED', 'payment_status' => 'CANCELLED']);
    analyticsOrder([[$item, 7]], ['status' => 'REFUNDED', 'payment_status' => 'REFUNDED']);
    analyticsOrder([[$item, 1]], ['status' => 'PENDING', 'payment_status' => 'PENDING']);

    expect(analyticsRow(app(ItemAnalyticsService::class)->popularity(null, null), 'Pancit'))
        ->toMatchArray(['units' => 3, 'orders' => 2, 'revenue' => 300.0]);
});

it('counts a line with no quantity (sold at a custom price) as one unit', function () {
    $item = analyticsMenu('Lechon (by weight)', '2026-10-01');

    analyticsOrder([[$item, null, 850.0]]);
    analyticsOrder([[$item, 2, 100.0]]);

    expect(analyticsRow(app(ItemAnalyticsService::class)->popularity(null, null), 'Lechon (by weight)'))
        ->toMatchArray(['units' => 3, 'orders' => 2, 'revenue' => 1050.0]);
});

it('counts an order once per item even when it has several lines of that item', function () {
    $item = analyticsMenu('Halo-halo', '2026-10-01');

    analyticsOrder([[$item, 1], [$item, 2]]);

    expect(analyticsRow(app(ItemAnalyticsService::class)->popularity(null, null), 'Halo-halo'))
        ->toMatchArray(['units' => 3, 'orders' => 1]);
});

it('only counts menu days and sales inside the date range', function () {
    $old = analyticsMenu('Bulalo', '2026-09-01');
    $current = analyticsMenu($old->item, '2026-10-05');
    $other = analyticsMenu('Tapa', '2026-09-02');

    analyticsOrder([[$old, 10], [$other, 4]]);
    analyticsOrder([[$current, 2]]);

    $rows = app(ItemAnalyticsService::class)->popularity(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'));

    expect($rows->pluck('name')->all())->toBe(['Bulalo'])
        ->and($rows->first())->toMatchArray(['appearances' => 1, 'units' => 2]);
});

it('can hide items that were on the menu fewer days than a minimum', function () {
    $often = analyticsMenu('Rice', '2026-10-01');
    analyticsMenu($often->item, '2026-10-02');
    analyticsMenu('One-off', '2026-10-01');

    $rows = app(ItemAnalyticsService::class)->popularity(null, null, 'total', 2);

    expect($rows->pluck('name')->all())->toBe(['Rice']);
});

it('gives tied items the same rank and skips the next one', function () {
    $a = analyticsMenu('A', '2026-10-01');
    $b = analyticsMenu('B', '2026-10-01');
    $c = analyticsMenu('C', '2026-10-01');

    analyticsOrder([[$a, 5], [$b, 5], [$c, 1]]);

    $rows = app(ItemAnalyticsService::class)->popularity(null, null);

    expect(analyticsRow($rows, 'A')['rank_total'])->toBe(1)
        ->and(analyticsRow($rows, 'B')['rank_total'])->toBe(1)
        ->and(analyticsRow($rows, 'C')['rank_total'])->toBe(3);
});

it('builds a daily report with what sold, how many, and what did not', function () {
    $adobo = analyticsMenu('Adobo', '2026-10-10', 120);
    $sinigang = analyticsMenu('Sinigang', '2026-10-10', 150);
    $unsold = analyticsMenu('Leche Flan', '2026-10-10', 80);
    $otherDay = analyticsMenu('Tapa', '2026-10-11');

    analyticsOrder([[$adobo, 2, 120.0], [$sinigang, 1, 150.0]]);
    analyticsOrder([[$adobo, 1, 120.0]]);
    analyticsOrder([[$sinigang, 4, 150.0]], ['status' => 'CANCELLED', 'payment_status' => 'CANCELLED']);
    analyticsOrder([[$otherDay, 9]]);

    $report = app(ItemAnalyticsService::class)->dailyReport(CarbonImmutable::parse('2026-10-10'));

    expect($report['rows']->pluck('name')->all())->toBe(['Adobo', 'Sinigang', 'Leche Flan'])
        ->and($report['rows']->firstWhere('name', 'Adobo'))->toMatchArray(['units' => 3, 'orders' => 2, 'revenue' => 360.0, 'price' => 120.0])
        ->and($report['rows']->firstWhere('name', 'Sinigang'))->toMatchArray(['units' => 1, 'orders' => 1, 'revenue' => 150.0])
        ->and($report['rows']->firstWhere('name', 'Leche Flan'))->toMatchArray(['units' => 0, 'orders' => 0, 'revenue' => 0.0])
        // Two orders in total: the first one has two of the items, which is still one order.
        ->and($report['totals'])->toMatchArray(['units' => 4, 'orders' => 2, 'revenue' => 510.0, 'items_sold' => 2, 'items_on_menu' => 3]);
});

it('returns an empty daily report for a day with no menu', function () {
    $report = app(ItemAnalyticsService::class)->dailyReport(CarbonImmutable::parse('2026-10-10'));

    expect($report['rows'])->toBeEmpty()
        ->and($report['totals'])->toMatchArray(['units' => 0, 'orders' => 0, 'items_on_menu' => 0]);
});

it('turns presets and custom dates into a range', function () {
    $service = app(ItemAnalyticsService::class);

    expect($service->range('all'))->toMatchArray(['from' => null, 'to' => null, 'label' => 'All time'])
        ->and($service->range('today')['from']->isToday())->toBeTrue()
        ->and($service->range('last30')['from']->diffInDays(today()))->toEqual(29);

    $swapped = $service->range('custom', CarbonImmutable::parse('2026-10-20'), CarbonImmutable::parse('2026-10-05'));

    expect($swapped['from']->toDateString())->toBe('2026-10-05')
        ->and($swapped['to']->toDateString())->toBe('2026-10-20');
});

it('shows admins the item analytics page', function () {
    $item = analyticsMenu('Adobo', today()->toDateString());
    analyticsOrder([[$item, 2]], ['transaction_date' => now()]);

    $admin = User::create(['username' => 'admin-' . uniqid(), 'password' => 'secret-pass', 'role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('analytics.items'))
        ->assertOk()
        ->assertSee('Item popularity')
        ->assertSee('Adobo');
});

it('falls back to defaults when the filters are nonsense', function () {
    $admin = User::create(['username' => 'admin-' . uniqid(), 'password' => 'secret-pass', 'role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('analytics.items', ['range' => 'bogus', 'sort' => 'bogus', 'date' => '2026-02-31', 'date_from' => 'x', 'min_appearances' => '-4']))
        ->assertOk();
});

it('keeps item analytics to admins', function () {
    foreach (['cashier', 'rider'] as $role) {
        $user = User::create(['username' => $role . '-' . uniqid(), 'password' => 'secret-pass', 'role' => $role]);

        $this->actingAs($user)->get(route('analytics.items'))->assertForbidden();
    }
});
