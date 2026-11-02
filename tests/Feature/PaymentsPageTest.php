<?php

use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use App\Services\PaymentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

/** An order worth $total, optionally with attributes such as status or type. */
function paymentsOrder(float $total = 100.0, array $attributes = []): Transaction
{
    return BackupData::transaction(['total' => $total] + $attributes);
}

/** A payment recorded at a given moment. The balance left is worked out like the app does it. */
function paymentsTaken(Transaction $order, float $amount, string $method, string $at, float $alreadyPaid = 0.0): TransactionPayment
{
    $payment = TransactionPayment::create([
        'transaction_id' => $order->id,
        'payment_method' => $method,
        'payment_amount' => $amount,
        'total_balance' => $order->total,
        'outstanding_balance' => round((float) $order->total - $alreadyPaid - $amount, 2),
    ]);

    $payment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

    return $payment;
}

function paymentsUser(string $role): User
{
    return User::create(['username' => $role . '-' . uniqid(), 'password' => 'secret-pass', 'role' => $role]);
}

const PAYMENTS_DAY = ['date_from' => '2026-10-27', 'date_to' => '2026-10-27'];

it('totals what came in for the day for every payment method, zeros included', function () {
    paymentsTaken(paymentsOrder(300), 300, 'CASH', '2026-10-27 09:00:00');
    paymentsTaken(paymentsOrder(200), 200, 'CASH', '2026-10-27 10:00:00');
    paymentsTaken(paymentsOrder(150), 150, 'GCASH', '2026-10-27 11:00:00');

    $summary = app(PaymentReportService::class)->summary(PAYMENTS_DAY);

    expect($summary['methods']->keys()->all())->toBe(['CASH', 'GCASH', 'BANK TRANSFER'])
        ->and($summary['methods']['CASH'])->toMatchArray(['payments' => 2, 'paid' => 500.0, 'change' => 0.0, 'received' => 500.0])
        ->and($summary['methods']['GCASH'])->toMatchArray(['payments' => 1, 'received' => 150.0])
        ->and($summary['methods']['BANK TRANSFER'])->toMatchArray(['payments' => 0, 'received' => 0.0])
        ->and($summary['total'])->toMatchArray(['payments' => 3, 'received' => 650.0]);
});

it('counts only what stayed in the till when change was given back', function () {
    // Paid 500 for a 450 order: 50 went back as change.
    paymentsTaken(paymentsOrder(450), 500, 'CASH', '2026-10-27 09:00:00');

    $cash = app(PaymentReportService::class)->summary(PAYMENTS_DAY)['methods']['CASH'];

    expect($cash)->toMatchArray(['paid' => 500.0, 'change' => 50.0, 'received' => 450.0]);
});

it('works out change per payment, so a part payment followed by an overpayment adds up', function () {
    $order = paymentsOrder(500);
    paymentsTaken($order, 200, 'GCASH', '2026-10-27 09:00:00');
    paymentsTaken($order, 400, 'CASH', '2026-10-28 09:00:00', alreadyPaid: 200);

    $summary = app(PaymentReportService::class)->summary(['date_from' => '2026-10-27', 'date_to' => '2026-10-28']);

    expect($summary['methods']['GCASH']['received'])->toBe(200.0)
        ->and($summary['methods']['CASH'])->toMatchArray(['paid' => 400.0, 'change' => 100.0, 'received' => 300.0])
        ->and($summary['total']['received'])->toBe(500.0);
});

it('puts a payment on the day it was recorded, not the day of its order', function () {
    $order = paymentsOrder(100, ['transaction_date' => '2026-10-01 12:00:00']);
    paymentsTaken($order, 100, 'CASH', '2026-10-27 09:00:00');

    $service = app(PaymentReportService::class);

    expect($service->summary(PAYMENTS_DAY)['total']['received'])->toBe(100.0)
        ->and($service->summary(['date_from' => '2026-10-01', 'date_to' => '2026-10-01'])['total']['received'])->toBe(0.0);
});

it('includes both end dates and nothing outside them', function () {
    paymentsTaken(paymentsOrder(10), 10, 'CASH', '2026-10-26 23:59:59');
    paymentsTaken(paymentsOrder(20), 20, 'CASH', '2026-10-27 00:00:00');
    paymentsTaken(paymentsOrder(40), 40, 'CASH', '2026-10-28 23:59:59');
    paymentsTaken(paymentsOrder(80), 80, 'CASH', '2026-10-29 00:00:00');

    $summary = app(PaymentReportService::class)->summary(['date_from' => '2026-10-27', 'date_to' => '2026-10-28']);

    expect($summary['total']['received'])->toBe(60.0);
});

it('leaves payments on cancelled and refunded orders out unless asked, and says what was left out', function () {
    paymentsTaken(paymentsOrder(100), 100, 'CASH', '2026-10-27 09:00:00');
    paymentsTaken(paymentsOrder(70, ['status' => 'CANCELLED', 'payment_status' => 'CANCELLED']), 70, 'CASH', '2026-10-27 09:30:00');
    paymentsTaken(paymentsOrder(30, ['status' => 'REFUNDED', 'payment_status' => 'REFUNDED']), 30, 'GCASH', '2026-10-27 10:00:00');

    $service = app(PaymentReportService::class);

    expect($service->summary(PAYMENTS_DAY)['total']['received'])->toBe(100.0)
        ->and($service->summary(PAYMENTS_DAY + ['scope' => 'void'])['total']['received'])->toBe(100.0)
        ->and($service->summary(PAYMENTS_DAY + ['scope' => 'all'])['total']['received'])->toBe(200.0)
        ->and($service->excluded(PAYMENTS_DAY))->toMatchArray(['payments' => 2, 'received' => 100.0]);
});

it('filters by payment method, order type and reference number', function () {
    $walkIn = paymentsOrder(100, ['reference_number' => 'LF-AAA111']);
    $delivery = paymentsOrder(200, ['reference_number' => 'LF-BBB222', 'type' => 'DELIVERY']);
    paymentsTaken($walkIn, 100, 'CASH', '2026-10-27 09:00:00');
    paymentsTaken($delivery, 200, 'GCASH', '2026-10-27 10:00:00');

    $service = app(PaymentReportService::class);

    expect($service->summary(PAYMENTS_DAY + ['method' => 'GCASH'])['total']['received'])->toBe(200.0)
        ->and($service->summary(PAYMENTS_DAY + ['type' => 'WALK IN'])['total']['received'])->toBe(100.0)
        ->and($service->summary(PAYMENTS_DAY + ['search' => 'BBB'])['total']['received'])->toBe(200.0)
        ->and($service->summary(PAYMENTS_DAY + ['method' => 'CASH', 'search' => 'BBB'])['total']['received'])->toBe(0.0);
});

it('breaks the money down by day and method, newest day first', function () {
    paymentsTaken(paymentsOrder(100), 100, 'CASH', '2026-10-27 09:00:00');
    paymentsTaken(paymentsOrder(50), 50, 'GCASH', '2026-10-27 21:00:00');
    paymentsTaken(paymentsOrder(80), 80, 'CASH', '2026-10-28 09:00:00');

    $result = app(PaymentReportService::class)->byDay(['date_from' => '2026-10-27', 'date_to' => '2026-10-28']);

    expect($result['days']->map(fn ($day) => $day['day']->toDateString())->all())->toBe(['2026-10-28', '2026-10-27'])
        ->and($result['days'][0]['methods'])->toBe(['CASH' => 80.0, 'GCASH' => 0.0, 'BANK TRANSFER' => 0.0])
        ->and($result['days'][1])->toMatchArray(['received' => 150.0])
        ->and($result['days'][1]['methods'])->toBe(['CASH' => 100.0, 'GCASH' => 50.0, 'BANK TRANSFER' => 0.0])
        ->and($result['truncated'])->toBeFalse();
});

it('only shows the latest days when there are more than the limit', function () {
    foreach (range(1, 5) as $day) {
        paymentsTaken(paymentsOrder(10), 10, 'CASH', "2026-10-0{$day} 09:00:00");
    }

    $result = app(PaymentReportService::class)->byDay([], limit: 3);

    expect($result['days'])->toHaveCount(3)
        ->and($result['days']->first()['day']->toDateString())->toBe('2026-10-05')
        ->and($result['truncated'])->toBeTrue();
});

it('lists payments with their order details, newest first, and can sort by what was received', function () {
    paymentsTaken(paymentsOrder(450, ['reference_number' => 'LF-OLD']), 500, 'CASH', '2026-10-27 08:00:00');
    paymentsTaken(paymentsOrder(300, ['reference_number' => 'LF-NEW', 'type' => 'DELIVERY']), 300, 'GCASH', '2026-10-27 18:00:00');

    $service = app(PaymentReportService::class);
    $page = $service->payments(PAYMENTS_DAY, 'created_at', 'desc', 15);

    expect($page->pluck('reference_number')->all())->toBe(['LF-NEW', 'LF-OLD'])
        ->and($page->first()->transaction_type)->toBe('DELIVERY')
        ->and($page->first()->transaction_status)->toBe('COMPLETED')
        ->and($page->last()->changeGiven())->toBe(50.0)
        ->and($page->last()->amountReceived())->toBe(450.0);

    // 450 received beats 300 received, even though 500 was handed over for the first.
    expect($service->payments(PAYMENTS_DAY, 'received', 'desc', 15)->pluck('reference_number')->all())->toBe(['LF-OLD', 'LF-NEW'])
        ->and($service->payments(PAYMENTS_DAY, 'received', 'asc', 15)->pluck('reference_number')->all())->toBe(['LF-NEW', 'LF-OLD'])
        ->and($service->payments(PAYMENTS_DAY, 'reference_number', 'asc', 15)->pluck('reference_number')->all())->toBe(['LF-NEW', 'LF-OLD']);
});

it('lets admins and cashiers open the payments page, but not riders', function () {
    $this->actingAs(paymentsUser('admin'))->get(route('payments.index'))->assertOk();
    $this->actingAs(paymentsUser('cashier'))->get(route('payments.index'))->assertOk();
    $this->actingAs(paymentsUser('rider'))->get(route('payments.index'))->assertForbidden();
});

it('sends guests to the login page', function () {
    $this->get(route('payments.index'))->assertRedirect();
});

it('opens on today when no dates are given, and shows every date once the dates are cleared', function () {
    paymentsTaken(paymentsOrder(120, ['reference_number' => 'LF-TODAY']), 120, 'CASH', now()->format('Y-m-d 09:00:00'));
    paymentsTaken(paymentsOrder(80, ['reference_number' => 'LF-YESTERDAY']), 80, 'GCASH', now()->subDay()->format('Y-m-d 09:00:00'));

    $cashier = paymentsUser('cashier');

    $this->actingAs($cashier)->get(route('payments.index'))
        ->assertOk()
        ->assertSee('LF-TODAY')
        ->assertDontSee('LF-YESTERDAY')
        ->assertSee('₱120.00');

    $this->actingAs($cashier)->get(route('payments.index', ['date_from' => '', 'date_to' => '']))
        ->assertOk()
        ->assertSee('LF-TODAY')
        ->assertSee('LF-YESTERDAY')
        ->assertSee('Day by day');
});

it('shows the amount received per method on the page', function () {
    paymentsTaken(paymentsOrder(450), 500, 'CASH', '2026-10-27 09:00:00');
    paymentsTaken(paymentsOrder(275.5), 275.5, 'GCASH', '2026-10-27 10:00:00');

    $this->actingAs(paymentsUser('admin'))
        ->get(route('payments.index', PAYMENTS_DAY))
        ->assertOk()
        ->assertSeeInOrder(['Cash', '₱450.00', 'GCash', '₱275.50', 'Bank Transfer', '₱0.00', 'Total Received', '₱725.50'])
        ->assertSee('₱500.00 paid, ₱50.00 change');
});

it('falls back to defaults when the filters are nonsense', function () {
    $this->actingAs(paymentsUser('admin'))
        ->get(route('payments.index', [
            'date_from' => '2026-02-31', 'date_to' => 'x', 'method' => 'BITCOIN', 'type' => 'nope',
            'scope' => 'nope', 'sort_by' => 'password', 'sort_order' => 'sideways', 'search' => str_repeat('a', 500),
        ]))
        ->assertOk();
});
