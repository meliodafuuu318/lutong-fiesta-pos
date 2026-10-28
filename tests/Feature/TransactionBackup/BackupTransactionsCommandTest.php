<?php

use App\Mail\TransactionBackupMail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Nothing here sends a real email or touches a real database: mail is faked and
    // phpunit.xml points the tests at an in-memory SQLite database.
    Mail::fake();
    config([
        'mail.default' => 'smtp',
        'backup.transactions.email' => 'recovery@example.org',
    ]);
});

it('emails the backup to the recovery mailbox', function () {
    BackupData::simpleSale();
    BackupData::simpleSale();

    $this->artisan('backup:transactions')
        ->expectsOutputToContain('Exported 2 transactions, 2 items, 2 payments and 0 deliveries')
        ->assertExitCode(0);

    Mail::assertSent(TransactionBackupMail::class, 1);
    Mail::assertSent(TransactionBackupMail::class, function (TransactionBackupMail $mail) {
        return $mail->hasTo('recovery@example.org')
            && $mail->backup->counts()['transactions'] === 2
            && json_validate($mail->backup->attachmentData);
    });
});

it('sends a backup even when there are no transactions yet', function () {
    $this->artisan('backup:transactions')->assertExitCode(0);

    Mail::assertSent(TransactionBackupMail::class, fn ($mail) => $mail->backup->counts()['transactions'] === 0);
});

it('does a dry run without emailing and without needing a recipient', function () {
    config(['backup.transactions.email' => null, 'mail.default' => 'array']);
    BackupData::simpleSale();

    $this->artisan('backup:transactions --dry-run')
        ->expectsOutputToContain('Exported 1 transactions')
        ->expectsOutputToContain('nothing was emailed')
        ->assertExitCode(0);

    Mail::assertNothingSent();
});

it('fails when the recipient is missing or invalid, and sends nothing', function (?string $email) {
    config(['backup.transactions.email' => $email]);
    Log::spy();

    $this->artisan('backup:transactions')
        ->expectsOutputToContain('TRANSACTION_BACKUP_EMAIL')
        ->assertExitCode(1);

    Mail::assertNothingSent();
    Log::shouldHaveReceived('error')->once();
})->with([null, '', 'not-an-email']);

it('refuses a mailer that would not really deliver the backup', function (string $mailer) {
    config([
        'mail.default' => $mailer,
        'mail.mailers.failover.mailers' => ['smtp', 'log'],
    ]);

    $this->artisan('backup:transactions')
        ->expectsOutputToContain('does not deliver email')
        ->assertExitCode(1);

    Mail::assertNothingSent();
})->with(['log', 'array', 'failover']);

it('fails with exit code 1 and logs only safe details when the mail server rejects it', function () {
    BackupData::simpleSale();
    BackupData::delivery(BackupData::transaction(['type' => 'DELIVERY']), '99 Secret Lane, Makati');
    Log::spy();

    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('535 auth failed for user:s3cretpass@smtp.example.org, recipient recovery@example.org'));

    $exit = Artisan::call('backup:transactions');
    $output = Artisan::output();

    expect($exit)->toBe(1);
    expect($output)->toContain('(email)')->not->toContain('s3cretpass')->not->toContain('smtp.example.org')->not->toContain('Secret Lane');

    Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) {
        $logged = $message.json_encode($context);

        return $context['stage'] === 'email'
            && $context['error'] === RuntimeException::class
            && ! str_contains($logged, 's3cretpass')
            && ! str_contains($logged, 'Secret Lane')
            && ! str_contains($logged, 'recovery@example.org');
    });
});

it('fails with exit code 1 when the database cannot be read, without exposing the query', function () {
    BackupData::simpleSale();
    Schema::drop('transaction_payments');
    Log::spy();

    $exit = Artisan::call('backup:transactions');
    $output = Artisan::output();

    expect($exit)->toBe(1);
    expect($output)->toContain('(export)')->not->toContain('select ')->not->toContain('SQLSTATE');

    Mail::assertNothingSent();
    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message, array $context) => $context['stage'] === 'export'
        && $context['error'] === QueryException::class);
});

it('compresses an oversize backup and still sends all of it', function () {
    foreach (range(1, 60) as $n) {
        BackupData::simpleSale();
    }
    config(['backup.transactions.max_attachment_mb' => 0.02]);

    $this->artisan('backup:transactions')->expectsOutputToContain('compressed')->assertExitCode(0);

    Mail::assertSent(TransactionBackupMail::class, function ($mail) {
        return $mail->backup->compressed
            && json_decode(gzdecode($mail->backup->attachmentData), true)['manifest']['record_counts']['transactions'] === 60;
    });
});

it('fails instead of truncating when the backup cannot fit the limit', function () {
    foreach (range(1, 20) as $n) {
        BackupData::simpleSale();
    }
    config(['backup.transactions.max_attachment_mb' => 0.0001]);

    $this->artisan('backup:transactions')
        ->expectsOutputToContain('Nothing was sent')
        ->assertExitCode(1);

    Mail::assertNothingSent();
});

it('does not start while another backup is running', function () {
    $lock = Cache::store('database')->lock('transaction-backup', 60);
    expect($lock->get())->toBeTrue();

    $this->artisan('backup:transactions')->expectsOutputToContain('already running')->assertExitCode(2);
    Mail::assertNothingSent();

    $lock->release();

    $this->artisan('backup:transactions')->assertExitCode(0);
    Mail::assertSent(TransactionBackupMail::class, 1);
});

it('releases the lock when it finishes, even if it failed', function () {
    config(['backup.transactions.max_attachment_mb' => 0.0001]);
    BackupData::simpleSale();

    $this->artisan('backup:transactions')->assertExitCode(1);

    $lock = Cache::store('database')->lock('transaction-backup', 60);
    expect($lock->get())->toBeTrue();
    $lock->release();
});

it('still backs up when the lock store cannot be used', function () {
    config(['backup.transactions.lock_store' => 'no-such-store']);
    BackupData::simpleSale();

    $this->artisan('backup:transactions')->assertExitCode(0);

    Mail::assertSent(TransactionBackupMail::class, 1);
});

it('leaves the transactions alone', function () {
    BackupData::simpleSale();
    $before = DB::table('transactions')->get()->toArray();

    $this->artisan('backup:transactions')->assertExitCode(0);

    expect(DB::table('transactions')->get()->toArray())->toEqual($before);
});
