<?php

use App\Models\DailyItem;
use App\Models\Expense;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

it('matches daily items throughout one calendar day with onDate', function () {
    $item = BackupData::menuItem('Adobo', 120, '2026-10-27');
    $otherDay = BackupData::menuItem('Sinigang', 150, '2026-10-28');

    expect(DailyItem::query()->onDate(CarbonImmutable::parse('2026-10-27'))->pluck('id')->all())
        ->toBe([$item->id])
        ->not->toContain($otherDay->id);
});

it('includes today and excludes tomorrow in the expense scope', function () {
    $today = Expense::create([
        'amount' => 100,
        'description' => 'Today',
        'expense_date' => now()->toDateString(),
    ]);
    Expense::create([
        'amount' => 200,
        'description' => 'Tomorrow',
        'expense_date' => now()->addDay()->toDateString(),
    ]);

    expect(Expense::today()->pluck('id')->all())->toBe([$today->id]);
});

it('includes the full current day and excludes void and next-day transactions', function () {
    $this->travelTo('2026-10-27 12:00:00');

    $first = BackupData::transaction([
        'reference_number' => 'LF-FIRST',
        'transaction_date' => '2026-10-27 00:00:00',
        'status' => 'COMPLETED',
    ]);
    $last = BackupData::transaction([
        'reference_number' => 'LF-LAST',
        'transaction_date' => '2026-10-27 23:59:59',
        'status' => 'COMPLETED',
    ]);
    BackupData::transaction([
        'reference_number' => 'LF-VOID',
        'transaction_date' => '2026-10-27 12:00:00',
        'status' => 'CANCELLED',
    ]);
    BackupData::transaction([
        'reference_number' => 'LF-NEXT',
        'transaction_date' => '2026-10-28 00:00:00',
        'status' => 'COMPLETED',
    ]);

    expect(Transaction::today()->pluck('id')->all())
        ->toBe([$first->id, $last->id]);
});
