<?php

use App\Models\DailyItem;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

/** Today's menu item priced at ₱100, so a quantity of 2 makes a ₱200 order. */
function todaysItem(): DailyItem
{
    return BackupData::menuItem('Adobo', 100, today()->toDateString());
}

/** @param array<string, mixed> $overrides */
function orderData(DailyItem $item, array $overrides = []): array
{
    return array_merge([
        'type' => 'WALK IN',
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 2]],
        'payment_method' => 'CASH',
        'payment_amount' => 200,
    ], $overrides);
}

it('marks a walk in as PAID and COMPLETED when the payment covers the total', function () {
    $transaction = app(TransactionService::class)->create(orderData(todaysItem()));

    expect($transaction->payment_status)->toBe('PAID')
        ->and($transaction->status)->toBe('COMPLETED')
        ->and($transaction->outstandingBalance())->toBe(0.0)
        ->and($transaction->transactionPayments)->toHaveCount(1);
});

it('marks a walk in as PAID when the payment is more than the total, and works out the change', function () {
    $transaction = app(TransactionService::class)->create(orderData(todaysItem(), ['payment_amount' => 500]));

    expect($transaction->payment_status)->toBe('PAID')
        ->and($transaction->status)->toBe('COMPLETED')
        ->and($transaction->change())->toBe(300.0);
});

it('leaves a partly paid walk in PENDING with the balance still owed', function () {
    $transaction = app(TransactionService::class)->create(orderData(todaysItem(), ['payment_amount' => 150]));

    expect($transaction->payment_status)->toBe('PENDING')
        ->and($transaction->status)->toBe('PENDING')
        ->and($transaction->outstandingBalance())->toBe(50.0);
});

it('creates a delivery with no payment as PENDING', function () {
    $transaction = app(TransactionService::class)->create(orderData(todaysItem(), [
        'type' => 'DELIVERY',
        'address' => '12 Mabini St',
        'payment_method' => null,
        'payment_amount' => null,
    ]));

    expect($transaction->payment_status)->toBe('PENDING')
        ->and($transaction->status)->toBe('PENDING')
        ->and($transaction->transactionPayments)->toBeEmpty()
        ->and($transaction->delivery->address)->toBe('12 Mabini St');
});

it('takes a pre-payment on a delivery: PAID, but still PENDING until it is delivered', function () {
    $service = app(TransactionService::class);

    $transaction = $service->create(orderData(todaysItem(), [
        'type' => 'DELIVERY',
        'address' => '12 Mabini St',
        'payment_method' => 'GCASH',
        'payment_amount' => 200,
    ]));

    expect($transaction->payment_status)->toBe('PAID')
        ->and($transaction->status)->toBe('PENDING')
        ->and($transaction->transactionPayments)->toHaveCount(1);

    $service->markDelivered($transaction);

    expect($transaction->refresh()->status)->toBe('COMPLETED');
});

it('leaves a part-paid delivery PENDING so the rider can collect the rest', function () {
    $transaction = app(TransactionService::class)->create(orderData(todaysItem(), [
        'type' => 'DELIVERY',
        'address' => '12 Mabini St',
        'payment_amount' => 50,
    ]));

    expect($transaction->payment_status)->toBe('PENDING')
        ->and($transaction->outstandingBalance())->toBe(150.0);
});

it('saves an optional note on walk ins and deliveries', function () {
    $service = app(TransactionService::class);
    $item = todaysItem();

    $walkIn = $service->create(orderData($item, ['note' => '  No onions  ']));
    $delivery = $service->create(orderData($item, [
        'type' => 'DELIVERY',
        'address' => '12 Mabini St',
        'payment_method' => null,
        'payment_amount' => null,
        'note' => 'Call when outside',
    ]));
    $plain = $service->create(orderData($item));

    expect($walkIn->note)->toBe('No onions')
        ->and($delivery->note)->toBe('Call when outside')
        ->and($plain->note)->toBeNull();
});
