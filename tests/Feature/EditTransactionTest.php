<?php

use App\Exceptions\TransactionException;
use App\Models\DailyItem;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function editUser(string $role): User
{
    return User::create(['username' => $role . '-' . uniqid(), 'password' => 'secret-pass', 'role' => $role]);
}

function pendingDelivery(?DailyItem $item = null): Transaction
{
    $item ??= BackupData::menuItem('Adobo', 100, today()->toDateString());

    return app(TransactionService::class)->create([
        'type' => 'DELIVERY',
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 2]],
        'address' => '12 Mabini St',
        'note' => 'No spice',
    ]);
}

it('changes the items, address and note of an unpaid delivery and recalculates the total', function () {
    $adobo = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $sinigang = BackupData::menuItem('Sinigang', 150, today()->toDateString());
    $transaction = pendingDelivery($adobo);

    $updated = app(TransactionService::class)->update($transaction, [
        'transaction_items' => [
            ['daily_item_id' => $adobo->id, 'quantity' => 3],
            ['daily_item_id' => $sinigang->id, 'quantity' => 1],
        ],
        'address' => '99 Rizal Ave',
        'note' => '',
    ]);

    expect((float) $updated->total)->toBe(450.0)
        ->and($updated->transactionItems)->toHaveCount(2)
        ->and($updated->delivery->address)->toBe('99 Rizal Ave')
        ->and($updated->note)->toBeNull()
        ->and($updated->status)->toBe('PENDING')
        ->and($updated->type)->toBe('DELIVERY');
});

it('keeps the price an item was sold at when its quantity changes', function () {
    $adobo = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $transaction = pendingDelivery($adobo);
    $adobo->update(['price' => 130]); // the menu price went up after the order was taken

    $updated = app(TransactionService::class)->update($transaction, [
        'transaction_items' => [['daily_item_id' => $adobo->id, 'quantity' => 3]],
        'address' => '12 Mabini St',
    ]);

    expect((float) $updated->total)->toBe(300.0);
});

it('prices an item that is added now at the current menu price, and takes a custom serving as given', function () {
    $adobo = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $sinigang = BackupData::menuItem('Sinigang', 150, today()->toDateString());
    $transaction = pendingDelivery($adobo);

    $updated = app(TransactionService::class)->update($transaction, [
        'transaction_items' => [
            ['daily_item_id' => $adobo->id, 'quantity' => 1],
            ['daily_item_id' => $sinigang->id, 'unit_price' => 50],
        ],
        'address' => '12 Mabini St',
    ]);

    expect((float) $updated->total)->toBe(150.0);
});

it('lets an item already on the order stay when it has sold out, but not a sold out item being added', function () {
    $adobo = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $sinigang = BackupData::menuItem('Sinigang', 150, today()->toDateString());
    $transaction = pendingDelivery($adobo);
    $adobo->update(['status' => 'SOLD OUT']);
    $sinigang->update(['status' => 'SOLD OUT']);
    $service = app(TransactionService::class);

    $kept = $service->update($transaction, [
        'transaction_items' => [['daily_item_id' => $adobo->id, 'quantity' => 1]],
        'address' => '12 Mabini St',
    ]);
    expect((float) $kept->total)->toBe(100.0);

    expect(fn () => $service->update($transaction, [
        'transaction_items' => [['daily_item_id' => $sinigang->id, 'quantity' => 1]],
        'address' => '12 Mabini St',
    ]))->toThrow(TransactionException::class);
    expect((float) $transaction->fresh()->total)->toBe(100.0);
});

it('refuses an order that has a payment, even a part payment', function () {
    $item = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $service = app(TransactionService::class);
    $transaction = $service->create([
        'type' => 'WALK IN',
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 2]],
        'payment_method' => 'CASH',
        'payment_amount' => 50,
    ]);

    expect($transaction->canBeEdited())->toBeFalse()
        ->and(fn () => $service->update($transaction, [
            'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 1]],
        ]))->toThrow(TransactionException::class, 'payment');
});

it('refuses delivered, cancelled and refunded orders', function () {
    $service = app(TransactionService::class);

    $delivered = pendingDelivery();
    $service->markDelivered($delivered);

    $cancelled = pendingDelivery();
    $service->cancel($cancelled);

    $refunded = pendingDelivery();
    $service->refund($refunded);

    foreach ([$delivered, $cancelled, $refunded] as $transaction) {
        expect($transaction->fresh()->canBeEdited())->toBeFalse()
            ->and(fn () => $service->update($transaction, [
                'transaction_items' => [['daily_item_id' => $transaction->transactionItems->first()->daily_item_id, 'quantity' => 1]],
                'address' => 'Somewhere',
            ]))->toThrow(TransactionException::class);
    }
});

it('keeps the recorded balances right on an order taken with a ₱0 payment', function () {
    $item = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $service = app(TransactionService::class);
    $transaction = $service->create([
        'type' => 'WALK IN',
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 2]],
        'payment_method' => 'CASH',
        'payment_amount' => 0,
    ]);

    $service->update($transaction, ['transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 3]]]);

    $payment = $transaction->fresh()->transactionPayments->first();
    expect((float) $payment->total_balance)->toBe(300.0)
        ->and((float) $payment->outstanding_balance)->toBe(300.0)
        ->and($transaction->fresh()->payment_status)->toBe('PENDING');
});

it('lets an admin open the edit page and save a change, and sends them back to the order', function () {
    $adobo = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $transaction = pendingDelivery($adobo);

    $this->actingAs(editUser('admin'))
        ->get(route('transactions.edit', $transaction))
        ->assertOk()
        ->assertSee('Save Changes');

    $this->actingAs(editUser('admin'))
        ->put(route('transactions.update', $transaction), [
            'transaction_items' => [['daily_item_id' => $adobo->id, 'quantity' => 4]],
            'address' => '12 Mabini St',
            'from' => '/transactions',
        ])
        ->assertRedirect(route('transactions.show', ['transaction' => $transaction, 'from' => '/transactions']));

    expect((float) $transaction->fresh()->total)->toBe(400.0);
});

it('requires an address for a delivery and at least one item', function () {
    $adobo = BackupData::menuItem('Adobo', 100, today()->toDateString());
    $transaction = pendingDelivery($adobo);

    $this->actingAs(editUser('admin'))
        ->put(route('transactions.update', $transaction), ['transaction_items' => [['daily_item_id' => $adobo->id, 'quantity' => 1]], 'address' => ''])
        ->assertSessionHasErrors('address', errorBag: 'editTransaction');

    $this->actingAs(editUser('admin'))
        ->put(route('transactions.update', $transaction), ['address' => '12 Mabini St'])
        ->assertSessionHasErrors('transaction_items', errorBag: 'editTransaction');
});

it('sends an admin back to the order with a message when it can no longer be edited', function () {
    $transaction = pendingDelivery();
    app(TransactionService::class)->cancel($transaction);

    $this->actingAs(editUser('admin'))
        ->get(route('transactions.edit', $transaction))
        ->assertRedirect()
        ->assertSessionHasErrors('order');
});

it('keeps editing to admins only', function (string $role) {
    $transaction = pendingDelivery();
    $user = editUser($role);

    $this->actingAs($user)->get(route('transactions.edit', $transaction))->assertForbidden();
    $this->actingAs($user)->put(route('transactions.update', $transaction), [
        'transaction_items' => [['daily_item_id' => $transaction->transactionItems->first()->daily_item_id, 'quantity' => 1]],
        'address' => 'x',
    ])->assertForbidden();
})->with(['cashier', 'rider']);

it('shows the edit button on the order page to an admin only while it can be edited', function () {
    $transaction = pendingDelivery();

    $this->actingAs(editUser('admin'))->get(route('transactions.show', $transaction))->assertSee('Edit Transaction');
    $this->actingAs(editUser('cashier'))->get(route('transactions.show', $transaction))->assertDontSee('Edit Transaction');

    app(TransactionService::class)->markDelivered($transaction);
    $this->actingAs(editUser('admin'))->get(route('transactions.show', $transaction))->assertDontSee('Edit Transaction');
});
