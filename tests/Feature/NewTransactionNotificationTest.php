<?php

use App\Models\User;
use App\Notifications\NewTransactionNotification;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

/** A user with push turned on in one browser, or without it when $subscribed is false. */
function pushUser(string $role, bool $subscribed = true): User
{
    $user = User::create(['username' => $role . '-' . uniqid(), 'password' => 'secret-pass', 'role' => $role]);

    if ($subscribed) {
        $user->updatePushSubscription('https://push.example.test/' . uniqid(), 'public-key', 'auth-token', 'aesgcm');
    }

    return $user;
}

/** @return array<string, mixed> */
function newOrder(string $type = 'WALK IN'): array
{
    $item = BackupData::menuItem('Adobo', 100, today()->toDateString());

    return $type === 'DELIVERY'
        ? ['type' => 'DELIVERY', 'address' => '12 Rizal St', 'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 1]]]
        : ['type' => 'WALK IN', 'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 1]], 'payment_method' => 'CASH', 'payment_amount' => 100];
}

it('notifies everyone with push turned on when an order is created', function () {
    Notification::fake();
    $admin = pushUser('admin');
    $cashier = pushUser('cashier');
    $notSubscribed = pushUser('admin', subscribed: false);

    app(TransactionService::class)->create(newOrder());

    Notification::assertSentTo([$admin, $cashier], NewTransactionNotification::class);
    Notification::assertNotSentTo($notSubscribed, NewTransactionNotification::class);
});

it("doesn't notify the person who took the order", function () {
    Notification::fake();
    $cashier = pushUser('cashier');
    $admin = pushUser('admin');

    $this->actingAs($cashier);
    app(TransactionService::class)->create(newOrder());

    Notification::assertNotSentTo($cashier, NewTransactionNotification::class);
    Notification::assertSentTo($admin, NewTransactionNotification::class);
});

it('only tells riders about deliveries', function () {
    Notification::fake();
    $rider = pushUser('rider');

    app(TransactionService::class)->create(newOrder('WALK IN'));
    Notification::assertNotSentTo($rider, NewTransactionNotification::class);

    app(TransactionService::class)->create(newOrder('DELIVERY'));
    Notification::assertSentTo($rider, NewTransactionNotification::class);
});

it('saves and removes a browser subscription for the signed in user', function () {
    $this->withoutVite();
    $user = pushUser('cashier', subscribed: false);
    $endpoint = 'https://push.example.test/device-1';

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token']])
        ->assertCreated();

    expect($user->pushSubscriptions()->where('endpoint', $endpoint)->exists())->toBeTrue();

    $this->actingAs($user)
        ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $endpoint])
        ->assertNoContent();

    expect($user->pushSubscriptions()->count())->toBe(0);
});
