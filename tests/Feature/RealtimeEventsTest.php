<?php

use App\Events\ExpenseCreated;
use App\Events\ExpenseDeleted;
use App\Events\ExpenseUpdated;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Events\MenuUpdated;
use App\Events\TransactionCreated;
use App\Events\TransactionUpdated;
use App\Models\Expense;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

function fakeRealtime(): void
{
    Event::fake([
        TransactionCreated::class, TransactionUpdated::class, MenuUpdated::class,
        ExpenseCreated::class, ExpenseUpdated::class, ExpenseDeleted::class,
        ItemCreated::class, ItemUpdated::class, ItemDeleted::class,
    ]);
}

function walkInOrder(): array
{
    $dailyItem = BackupData::menuItem('Adobo', 100, today()->toDateString());

    return [
        $dailyItem,
        ['type' => 'WALK IN', 'transaction_items' => [['daily_item_id' => $dailyItem->id, 'quantity' => 1]], 'payment_method' => 'CASH', 'payment_amount' => 50],
    ];
}

it('announces a new transaction, and each way a transaction changes', function () {
    [, $data] = walkInOrder();
    fakeRealtime();
    $service = app(TransactionService::class);

    $transaction = $service->create($data);
    Event::assertDispatched(TransactionCreated::class, fn ($e) => $e->id === $transaction->id);

    $service->addPayment($transaction, 'CASH', 50);
    Event::assertDispatched(TransactionUpdated::class, fn ($e) => $e->reason === 'payment');

    $service->refund($transaction);
    Event::assertDispatched(TransactionUpdated::class, fn ($e) => $e->reason === 'refunded');
});

it('announces a cancelled transaction', function () {
    [, $data] = walkInOrder();
    $transaction = app(TransactionService::class)->create($data);
    fakeRealtime();

    app(TransactionService::class)->cancel($transaction);

    Event::assertDispatched(TransactionUpdated::class, fn ($e) => $e->reason === 'cancelled');
});

it('announces menu changes', function () {
    $dailyItem = BackupData::menuItem('Adobo', 100, today()->toDateString());
    fakeRealtime();

    $dailyItem->update(['status' => 'SOLD OUT']);
    Event::assertDispatched(MenuUpdated::class, fn ($e) => $e->reason === 'status');

    $dailyItem->update(['price' => 120]);
    Event::assertDispatched(MenuUpdated::class, fn ($e) => $e->reason === 'updated');

    $dailyItem->delete();
    Event::assertDispatched(MenuUpdated::class, fn ($e) => $e->reason === 'removed');
});

it('announces item and expense changes', function () {
    fakeRealtime();

    $dailyItem = BackupData::menuItem('Sinigang', 90, today()->toDateString());
    Event::assertDispatched(ItemCreated::class);
    Event::assertDispatched(MenuUpdated::class, fn ($e) => $e->reason === 'added');

    $dailyItem->item->update(['name' => 'Sinigang na Baboy']);
    Event::assertDispatched(ItemUpdated::class);

    $dailyItem->delete();
    $dailyItem->item->delete();
    Event::assertDispatched(ItemDeleted::class);

    $expense = Expense::create(['amount' => 50, 'description' => 'Ice', 'expense_date' => today()]);
    Event::assertDispatched(ExpenseCreated::class);

    $expense->update(['amount' => 60]);
    Event::assertDispatched(ExpenseUpdated::class);

    $expense->delete();
    Event::assertDispatched(ExpenseDeleted::class);
});
