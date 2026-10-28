<?php

use App\Exceptions\BackupException;
use App\Models\Transaction;
use App\Services\TransactionBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

/** Run the export and decode the JSON that would be attached. */
function exportedBackup(): array
{
    $backup = app(TransactionBackupService::class)->export();
    $json = $backup->compressed ? gzdecode($backup->attachmentData) : $backup->attachmentData;

    return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    $this->travelTo(Carbon\Carbon::parse('2026-10-27 23:00:03', 'Asia/Manila'));
});

it('exports transactions with their items, payments and delivery, keeping the original ids', function () {
    $adobo = BackupData::menuItem('Adobo', 120);
    $rice = BackupData::menuItem('Rice', 25);

    $walkIn = BackupData::transaction([
        'total' => 265,
        'transaction_date' => Carbon\Carbon::parse('2026-10-27 11:30:00', 'Asia/Manila'),
    ]);
    $adoboLine = BackupData::line($walkIn, $adobo, 2, 120);
    $riceLine = BackupData::line($walkIn, $rice, 1, 25);
    $cash = BackupData::payment($walkIn, 300);

    $delivery = BackupData::transaction([
        'type' => 'DELIVERY',
        'status' => 'PENDING',
        'payment_status' => 'PENDING',
        'total' => 120,
        'transaction_date' => Carbon\Carbon::parse('2026-10-27 18:45:00', 'Asia/Manila'),
    ]);
    BackupData::line($delivery, $adobo, 1, 120);
    $address = BackupData::delivery($delivery, '12 Mabini St, Quezon City');
    $gcash = BackupData::payment($delivery, 50, 'GCASH');

    $data = exportedBackup();

    expect($data['transactions'])->toHaveCount(2);

    [$first, $second] = $data['transactions'];

    expect($first)->toMatchArray([
        'id' => $walkIn->id,
        'reference_number' => $walkIn->reference_number,
        'type' => 'WALK IN',
        'status' => 'COMPLETED',
        'payment_status' => 'PAID',
        'total' => '265.00',
        'transaction_date' => '2026-10-27T11:30:00+08:00',
        'delivery' => null,
    ]);
    expect($first['items'])->toHaveCount(2);
    expect($first['items'][0])->toMatchArray([
        'id' => $adoboLine->id,
        'daily_item_id' => $adobo->id,
        'quantity' => 2,
        'unit_price' => '120.00',
        'subtotal' => '240.00',
    ]);
    expect($first['items'][0]['item'])->toMatchArray(['id' => $adobo->item_id, 'name' => 'Adobo', 'category' => 'Mains']);
    expect($first['items'][0]['daily_item'])->toMatchArray(['id' => $adobo->id, 'date' => '2026-10-27', 'price' => '120.00']);
    expect($first['items'][1]['id'])->toBe($riceLine->id);
    expect($first['payments'])->toHaveCount(1);
    expect($first['payments'][0])->toMatchArray([
        'id' => $cash->id,
        'payment_method' => 'CASH',
        'payment_amount' => '300.00',
        'total_balance' => '265.00',
        'outstanding_balance' => '-35.00',
    ]);

    expect($second['id'])->toBe($delivery->id);
    expect($second['delivery'])->toMatchArray(['id' => $address->id, 'address' => '12 Mabini St, Quezon City', 'status' => 'PENDING']);
    expect($second['payments'][0])->toMatchArray(['id' => $gcash->id, 'payment_method' => 'GCASH', 'payment_amount' => '50.00']);
});

it('writes a manifest with the format, timestamp, timezone, counts and date range', function () {
    $first = BackupData::simpleSale();
    $first->forceFill(['transaction_date' => '2026-10-01 08:00:00'])->save();
    $last = BackupData::simpleSale();
    $last->forceFill(['transaction_date' => '2026-10-27 20:15:00'])->save();

    $backup = app(TransactionBackupService::class)->export();
    $manifest = json_decode($backup->attachmentData, true)['manifest'];

    expect($manifest)->toMatchArray([
        'format' => 'lutong-fiesta-transaction-backup',
        'format_version' => 1,
        'backup_type' => 'full',
        'exported_at' => '2026-10-27T23:00:03+08:00',
        'exported_at_utc' => '2026-10-27T15:00:03Z',
        'timezone' => 'Asia/Manila',
        'record_counts' => [
            'transactions' => 2,
            'transaction_items' => 2,
            'transaction_payments' => 2,
            'deliveries' => 0,
        ],
        'highest_transaction_id' => $last->id,
    ]);
    expect($manifest['date_range'])->toMatchArray([
        'from' => '2026-10-01T08:00:00+08:00',
        'to' => '2026-10-27T20:15:00+08:00',
    ]);
    expect($manifest['notes'])->not->toBeEmpty();

    // The manifest comes first in the file
    expect($backup->attachmentData)->toStartWith('{"manifest":');
    expect($backup->attachmentName)->toBe('lutong-fiesta-transactions-20261027-230003.json');
});

it('keeps the prices saved with each line when the menu or the item changes later', function () {
    $dailyItem = BackupData::menuItem('Lechon Kawali', 200);
    $transaction = BackupData::transaction(['total' => 400]);
    BackupData::line($transaction, $dailyItem, 2, 200);

    // Later: the menu price goes up and the item is renamed
    $dailyItem->update(['price' => 250]);
    $dailyItem->item->update(['name' => 'Crispy Pata', 'default_price' => 250]);

    $line = exportedBackup()['transactions'][0]['items'][0];

    expect($line['unit_price'])->toBe('200.00');
    expect($line['subtotal'])->toBe('400.00');
    expect($line['daily_item']['price'])->toBe('250.00');
    // The database keeps no copy of the name on the line, so this is the name today
    expect($line['item']['name'])->toBe('Crispy Pata');
});

it('exports lines sold at a custom price, which have no quantity', function () {
    $transaction = BackupData::transaction(['total' => 85.5]);
    BackupData::line($transaction, BackupData::menuItem('Party Tray', 1000), null, 85.5);

    $line = exportedBackup()['transactions'][0]['items'][0];

    expect($line['quantity'])->toBeNull();
    expect($line['unit_price'])->toBe('85.50');
    expect($line['subtotal'])->toBe('85.50');
});

it('keeps cancelled and refunded statuses on the transaction, its payment and its delivery', function () {
    $cancelled = BackupData::transaction(['type' => 'DELIVERY', 'status' => 'CANCELLED', 'payment_status' => 'CANCELLED', 'total' => 100]);
    BackupData::delivery($cancelled, '1 Rizal Ave', 'CANCELLED');

    $refunded = BackupData::transaction(['type' => 'DELIVERY', 'status' => 'REFUNDED', 'payment_status' => 'REFUNDED', 'total' => 100]);
    BackupData::delivery($refunded, '2 Rizal Ave', 'REFUNDED');
    BackupData::payment($refunded, 100);

    [$one, $two] = exportedBackup()['transactions'];

    expect([$one['status'], $one['payment_status'], $one['delivery']['status']])->toBe(['CANCELLED', 'CANCELLED', 'CANCELLED']);
    expect([$two['status'], $two['payment_status'], $two['delivery']['status']])->toBe(['REFUNDED', 'REFUNDED', 'REFUNDED']);
    // Nothing is dropped: a refunded transaction still has its payment
    expect($two['payments'])->toHaveCount(1);
});

it('produces valid JSON for an empty history', function () {
    $backup = app(TransactionBackupService::class)->export();

    expect(json_validate($backup->attachmentData))->toBeTrue();

    $data = json_decode($backup->attachmentData, true);

    expect($data['transactions'])->toBe([]);
    expect($data['manifest']['record_counts'])->toBe([
        'transactions' => 0,
        'transaction_items' => 0,
        'transaction_payments' => 0,
        'deliveries' => 0,
    ]);
    expect($data['manifest']['date_range'])->toMatchArray(['from' => null, 'to' => null]);
    expect($data['manifest']['highest_transaction_id'])->toBeNull();
});

it('survives characters that are awkward in JSON', function () {
    $address = "Ñandú St. “Block 5”, Lot 2/3 \\ back gate 😀\nnear the \"red\" store";
    $transaction = BackupData::transaction(['type' => 'DELIVERY']);
    BackupData::delivery($transaction, $address);

    expect(exportedBackup()['transactions'][0]['delivery']['address'])->toBe($address);
});

it('exports the whole history every time, not just what is new', function () {
    BackupData::simpleSale();
    $service = app(TransactionBackupService::class);

    expect($service->export()->counts()['transactions'])->toBe(1);

    BackupData::simpleSale();
    BackupData::simpleSale();

    $second = $service->export();
    $ids = array_column(json_decode($second->attachmentData, true)['transactions'], 'id');

    expect($second->counts()['transactions'])->toBe(3);
    expect($ids)->toBe(Transaction::orderBy('id')->pluck('id')->all());
});

it('only reads: it never writes to the database', function () {
    BackupData::simpleSale();
    $before = [Transaction::count(), DB::table('transaction_items')->count(), Transaction::first()->updated_at->toIso8601String()];

    DB::enableQueryLog();
    app(TransactionBackupService::class)->export();
    $statements = collect(DB::getQueryLog())->pluck('query')->map(fn ($sql) => strtolower(strtok(ltrim($sql), ' ')));

    expect($statements->unique()->diff(['select'])->all())->toBe([]);
    expect([Transaction::count(), DB::table('transaction_items')->count(), Transaction::first()->updated_at->toIso8601String()])->toBe($before);
});

it('uses a handful of queries however many transactions there are', function () {
    $dailyItem = BackupData::menuItem();

    // A thousand-ish rows in one statement each, so the test stays quick
    DB::transaction(function () use ($dailyItem) {
        foreach (range(1, 450) as $n) {
            BackupData::line(BackupData::transaction(['total' => 120]), $dailyItem, 1, 120);
        }
    });

    DB::enableQueryLog();
    $backup = app(TransactionBackupService::class)->export();
    $queries = count(DB::getQueryLog());

    expect($backup->counts()['transactions'])->toBe(450);
    expect($backup->counts()['transaction_items'])->toBe(450);
    // 3 chunks of 200 with 5 queries each, plus the id and the count checks: nowhere near 450
    expect($queries)->toBeLessThan(30);
});

it('compresses a backup that is over the attachment limit instead of cutting it short', function () {
    $dailyItem = BackupData::menuItem();
    foreach (range(1, 60) as $n) {
        BackupData::line(BackupData::transaction(['total' => 120]), $dailyItem, 1, 120);
    }

    config(['backup.transactions.max_attachment_mb' => 0.02]); // about 20 KB

    $backup = app(TransactionBackupService::class)->export();

    expect($backup->compressed)->toBeTrue();
    expect($backup->attachmentName)->toEndWith('.json.gz');
    expect($backup->attachmentMime())->toBe('application/gzip');
    expect($backup->uncompressedBytes)->toBeGreaterThan(20 * 1024);
    expect($backup->attachmentBytes())->toBeLessThan(20 * 1024);

    // Complete once unzipped
    $data = json_decode(gzdecode($backup->attachmentData), true);
    expect($data['transactions'])->toHaveCount(60);
    expect($data['manifest']['record_counts']['transactions'])->toBe(60);
});

it('refuses a backup that is too big even when compressed, and says so', function () {
    foreach (range(1, 20) as $n) {
        BackupData::simpleSale();
    }

    config(['backup.transactions.max_attachment_mb' => 0.0001]); // about 100 bytes

    expect(fn () => app(TransactionBackupService::class)->export())
        ->toThrow(BackupException::class, 'Nothing was sent');
});

it('discards the export when it does not match the database', function () {
    $transaction = BackupData::simpleSale();
    $injected = false;

    // A payment appears while the export is running, after the payments were already read
    DB::listen(function ($query) use (&$injected, $transaction) {
        if (! $injected && str_contains($query->sql, 'from "deliveries"')) {
            $injected = true;
            BackupData::payment($transaction, 10);
        }
    });

    expect(fn () => app(TransactionBackupService::class)->export())
        ->toThrow(BackupException::class, 'does not match the database');
});
