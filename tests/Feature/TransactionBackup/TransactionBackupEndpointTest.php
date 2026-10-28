<?php

use App\Mail\TransactionBackupMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

const CRON_URL = '/api/cron/transaction-backup';

beforeEach(function () {
    Mail::fake();
    $this->secret = 'a-long-random-secret-for-the-tests-0123456789';
    config([
        'backup.transactions.cron_secret' => $this->secret,
        'backup.transactions.email' => 'recovery@example.org',
        'mail.default' => 'smtp',
    ]);
});

it('runs the backup for a request with the right bearer token', function () {
    BackupData::simpleSale();

    $this->withToken($this->secret)->getJson(CRON_URL)
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);

    Mail::assertSent(TransactionBackupMail::class, 1);
});

it('also accepts POST with the token', function () {
    $this->withToken($this->secret)->postJson(CRON_URL)->assertOk();

    Mail::assertSent(TransactionBackupMail::class, 1);
});

it('rejects requests without a token', function () {
    $this->getJson(CRON_URL)->assertUnauthorized();

    Mail::assertNothingSent();
});

it('rejects a wrong token, including one that only starts like the secret', function (string $token) {
    $this->withToken($token)->getJson(CRON_URL)->assertUnauthorized();

    Mail::assertNothingSent();
})->with([
    'wrong' => 'not-the-secret',
    'prefix' => 'a-long-random-secret-for-the-tests',
    'longer' => 'a-long-random-secret-for-the-tests-0123456789-and-more',
    'empty' => ' ',
]);

it('does not take the secret from the URL or the body', function () {
    $secret = $this->secret;

    $this->getJson(CRON_URL.'?secret='.$secret)->assertUnauthorized();
    $this->getJson(CRON_URL.'?token='.$secret.'&key='.$secret)->assertUnauthorized();
    $this->postJson(CRON_URL, ['secret' => $secret, 'token' => $secret])->assertUnauthorized();
    $this->withHeader('X-Cron-Secret', $secret)->getJson(CRON_URL)->assertUnauthorized();
    $this->withHeader('Authorization', $secret)->getJson(CRON_URL)->assertUnauthorized(); // no "Bearer"

    Mail::assertNothingSent();
});

it('stays closed when no secret is configured, whatever is sent', function (?string $configured) {
    config(['backup.transactions.cron_secret' => $configured]);

    $this->getJson(CRON_URL)->assertStatus(503);
    $this->withToken('')->getJson(CRON_URL)->assertStatus(503);
    $this->withToken('anything')->getJson(CRON_URL)->assertStatus(503);
    $this->withToken((string) $configured)->getJson(CRON_URL)->assertStatus(503);

    Mail::assertNothingSent();
})->with([null, '', 'short-secret-under-32-chars']);

it('answers only with a status, never with counts, errors or the secret', function () {
    BackupData::simpleSale();

    $ok = $this->withToken($this->secret)->getJson(CRON_URL);
    expect($ok->getContent())->toBe('{"status":"ok"}');

    config(['backup.transactions.email' => null]);
    $failed = $this->withToken($this->secret)->getJson(CRON_URL);

    $failed->assertStatus(500)->assertJson(['status' => 'failed']);
    expect($failed->getContent())->not->toContain($this->secret)->not->toContain('TRANSACTION_BACKUP_EMAIL');
    Mail::assertSent(TransactionBackupMail::class, 1);
});

it('answers 409 while another backup is running', function () {
    $lock = Cache::store('database')->lock('transaction-backup', 60);
    $lock->get();

    $this->withToken($this->secret)->getJson(CRON_URL)->assertStatus(409)->assertJson(['status' => 'skipped']);

    Mail::assertNothingSent();
    $lock->release();
});

it('does not need a signed in user, and is not part of the app for people', function () {
    // Signing in doesn't let anyone start a backup without the secret
    $this->actingAs(User::create(['username' => 'admin', 'password' => 'secret-password', 'role' => 'admin']))
        ->getJson(CRON_URL)
        ->assertUnauthorized();

    Mail::assertNothingSent();
});

it('is scheduled in vercel.json for 15:00 UTC, which is 23:00 in Manila', function () {
    $vercel = json_decode(file_get_contents(base_path('vercel.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($vercel['crons'])->toBe([['path' => CRON_URL, 'schedule' => '0 15 * * *']]);
    expect(Carbon\Carbon::parse('2026-10-27 15:00:00', 'UTC')->setTimezone('Asia/Manila')->format('H:i'))->toBe('23:00');

    // The path reaches a real route (a 401 from our middleware, not a 404), and vercel.json sends every path to the PHP function
    $this->getJson($vercel['crons'][0]['path'])->assertUnauthorized();
    expect(collect($vercel['routes'])->last())->toBe(['src' => '/(.*)', 'dest' => '/api/index.php']);
});

it('has only placeholders in .env.example', function () {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)->toMatch('/^TRANSACTION_BACKUP_EMAIL=$/m');
    expect($example)->toMatch('/^CRON_SECRET=$/m');
    expect($example)->toMatch('/^TRANSACTION_BACKUP_MAX_ATTACHMENT_MB=15$/m');
});
