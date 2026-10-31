<?php

use App\Models\Expense;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

function userWithRole(string $role): User
{
    return User::create(['username' => $role . '-' . uniqid(), 'password' => 'secret-pass', 'role' => $role]);
}

/** A walk in order of ₱200 that has only been part-paid (₱50), so ₱150 is still owed. */
function partPaidOrder(): App\Models\Transaction
{
    $item = BackupData::menuItem('Adobo', 100, today()->toDateString());

    return app(TransactionService::class)->create([
        'type' => 'WALK IN',
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 2]],
        'payment_method' => 'CASH',
        'payment_amount' => 50,
    ]);
}

beforeEach(fn () => $this->withoutVite());

it('lets a cashier see the orders that still owe money, and only those', function () {
    $owing = partPaidOrder();
    $item = BackupData::menuItem('Sinigang', 100, today()->toDateString());
    $paid = app(TransactionService::class)->create([
        'type' => 'WALK IN',
        'transaction_items' => [['daily_item_id' => $item->id, 'quantity' => 1]],
        'payment_method' => 'CASH',
        'payment_amount' => 100,
    ]);

    $this->actingAs(userWithRole('cashier'))
        ->get(route('pending-payments'))
        ->assertOk()
        ->assertSee($owing->reference_number)
        ->assertDontSee($paid->reference_number);
});

it('keeps the pending payments page to admins and cashiers', function () {
    $this->actingAs(userWithRole('rider'))->get(route('pending-payments'))->assertForbidden();
});

it('lets a cashier add a payment, which settles the order when it covers the balance', function () {
    $order = partPaidOrder();

    $this->actingAs(userWithRole('cashier'))
        ->post(route('transactions.payments.store', $order), ['payment_method' => 'CASH', 'payment_amount' => 150])
        ->assertRedirect(route('pending-payments'));

    expect($order->refresh()->payment_status)->toBe('PAID')
        ->and($order->status)->toBe('COMPLETED');
});

it('lets a cashier cancel an order, but not refund one', function () {
    $order = partPaidOrder();
    $cashier = userWithRole('cashier');

    $this->actingAs($cashier)->patch(route('transactions.cancel', $order))->assertRedirect();
    expect($order->refresh()->status)->toBe('CANCELLED');

    $this->actingAs($cashier)->patch(route('transactions.refund', $order))->assertForbidden();
});

it('keeps a cashier out of the expenses', function () {
    $this->actingAs(userWithRole('cashier'))->get(route('expenses.index'))->assertForbidden();
});

it('accepts a negative expense as money coming in, but not zero', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)
        ->post(route('expenses.store'), ['amount' => '-120.50', 'description' => 'Change returned'])
        ->assertSessionHasNoErrors();

    expect((float) Expense::first()->amount)->toBe(-120.5);

    $this->actingAs($admin)
        ->post(route('expenses.store'), ['amount' => '0', 'description' => 'Nothing'])
        ->assertSessionHasErrors('amount', errorBag: 'expenseForm');

    expect(Expense::count())->toBe(1);
});

it('subtracts money that came back in from the expense totals', function () {
    Expense::create(['amount' => 1500, 'description' => 'Ingredients', 'expense_date' => today()]);
    Expense::create(['amount' => -200, 'description' => 'Change returned', 'expense_date' => today()]);

    expect(app(DashboardService::class)->dailyExpenses())->toBe(1300.0)
        ->and(app(DashboardService::class)->weekBreakdown()->firstWhere('is_today', true)['expenses'])->toBe(1300.0);
});
