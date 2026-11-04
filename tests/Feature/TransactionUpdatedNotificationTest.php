<?php

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\NewTransactionNotification;
use App\Notifications\TransactionUpdatedNotification;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

function updateUser(string $role, bool $subscribed = true): User
{
    $user = User::create(['username' => $role . '-' . uniqid(), 'password' => 'secret-pass', 'role' => $role]);

    if ($subscribed) {
        $user->updatePushSubscription('https://push.example.test/' . uniqid(), 'public-key', 'auth-token', 'aesgcm');
    }

    return $user;
}

/** An unpaid order of ₱200 (2 x ₱100), made while nobody is watching for notifications. */
function unpaidOrder(string $type = 'DELIVERY'): Transaction
{
    $item = BackupData::menuItem('Adobo', 100, today()->toDateString());

    return app(TransactionService::class)->create(array_filter([
        'type' => $type,
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 2]],
        'address' => $type === 'DELIVERY' ? '12 Rizal St' : null,
    ]));
}

it('notifies subscribed users when an order is edited', function () {
    $order = unpaidOrder();
    $item = $order->transactionItems->first();
    $admin = updateUser('admin');
    $notSubscribed = updateUser('cashier', subscribed: false);
    Notification::fake();

    app(TransactionService::class)->update($order, [
        'transaction_items' => [['daily_item_id' => $item->daily_item_id, 'quantity' => 3]],
        'address' => '99 Mabini St',
        'note' => '',
    ]);

    Notification::assertSentTo($admin, TransactionUpdatedNotification::class, fn ($n) => $n->toWebPush($admin, $n)->toArray()['title'] === 'Order updated');
    Notification::assertNotSentTo($notSubscribed, TransactionUpdatedNotification::class);
    Notification::assertNotSentTo($admin, NewTransactionNotification::class);
});

it('notifies subscribed users when a payment is added, saying how much', function () {
    $order = unpaidOrder();
    $cashier = updateUser('cashier');
    Notification::fake();

    app(TransactionService::class)->addPayment($order, 'CASH', 50);

    Notification::assertSentTo($cashier, TransactionUpdatedNotification::class, function ($n) use ($cashier) {
        $message = $n->toWebPush($cashier, $n)->toArray();

        return $message['title'] === 'Payment received' && str_contains($message['body'], '₱50.00');
    });
});

it('says so when a payment settles the order', function () {
    $order = unpaidOrder();
    $cashier = updateUser('cashier');
    Notification::fake();

    app(TransactionService::class)->addPayment($order, 'CASH', 200);

    Notification::assertSentTo($cashier, TransactionUpdatedNotification::class, fn ($n) => str_contains($n->toWebPush($cashier, $n)->toArray()['body'], 'fully paid'));
});

it('notifies subscribed users when a delivery is marked delivered', function () {
    $order = unpaidOrder();
    $rider = updateUser('rider');
    $admin = updateUser('admin');
    Notification::fake();

    app(TransactionService::class)->markDelivered($order);

    Notification::assertSentTo([$rider, $admin], TransactionUpdatedNotification::class);
    Notification::assertSentTo($admin, TransactionUpdatedNotification::class, fn ($n) => $n->toWebPush($admin, $n)->toArray()['title'] === 'Order delivered');
});

it('only tells riders about changes to deliveries', function () {
    $rider = updateUser('rider');
    $walkIn = unpaidOrder('WALK IN');
    Notification::fake();

    app(TransactionService::class)->addPayment($walkIn, 'CASH', 50);
    Notification::assertNotSentTo($rider, TransactionUpdatedNotification::class);

    app(TransactionService::class)->addPayment(unpaidOrder('DELIVERY'), 'CASH', 50);
    Notification::assertSentTo($rider, TransactionUpdatedNotification::class);
});

it('does not notify anyone when the change is refused', function () {
    $order = unpaidOrder('WALK IN'); // a walk in has no delivery to mark
    $admin = updateUser('admin');
    Notification::fake();

    expect(fn () => app(TransactionService::class)->markDelivered($order))->toThrow(\App\Exceptions\TransactionException::class);

    Notification::assertNothingSent();
});
