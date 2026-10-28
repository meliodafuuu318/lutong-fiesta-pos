<?php

use App\Mail\TransactionBackupMail;
use App\Services\TransactionBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\BackupData;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon\Carbon::parse('2026-10-27 23:00:03', 'Asia/Manila'));
});

it('attaches the JSON with a timestamped file name', function () {
    BackupData::simpleSale();
    $backup = app(TransactionBackupService::class)->export();

    $mail = new TransactionBackupMail($backup);

    $mail->assertHasAttachedData($backup->attachmentData, 'lutong-fiesta-transactions-20261027-230003.json', ['mime' => 'application/json']);
    expect(json_validate($backup->attachmentData))->toBeTrue();
});

it('has the app name and the export time in the subject', function () {
    $mail = new TransactionBackupMail(app(TransactionBackupService::class)->export());

    $mail->assertHasSubject(config('app.name').' transaction backup, 2026-10-27 23:00');
});

it('summarises the record counts and the file in the body', function () {
    $first = BackupData::simpleSale();
    $first->forceFill(['transaction_date' => '2026-10-01 08:00:00'])->save();
    BackupData::simpleSale();
    BackupData::delivery(BackupData::transaction(['type' => 'DELIVERY']), '5 Rizal Ave');

    $backup = app(TransactionBackupService::class)->export();
    $mail = new TransactionBackupMail($backup);

    $mail->assertSeeInText('Transactions:  3');
    $mail->assertSeeInText('Items:         2');
    $mail->assertSeeInText('Payments:      2');
    $mail->assertSeeInText('Deliveries:    1');
    $mail->assertSeeInText('Covers: 2026-10-01T08:00:00+08:00 to 2026-10-27T12:00:00+08:00');
    $mail->assertSeeInText('Asia/Manila');
    $mail->assertSeeInText($backup->attachmentName);
    $mail->assertSeeInText($backup->sha256());
    $mail->assertDontSeeInText('gzip');
});

it('keeps transaction contents out of the body', function () {
    $transaction = BackupData::simpleSale();
    BackupData::delivery(BackupData::transaction(['type' => 'DELIVERY']), '99 Secret Lane, Makati');

    $mail = new TransactionBackupMail(app(TransactionBackupService::class)->export());

    $mail->assertDontSeeInText('99 Secret Lane');
    $mail->assertDontSeeInText($transaction->reference_number);
    $mail->assertDontSeeInText('Adobo');
});

it('says so when the attachment is compressed', function () {
    foreach (range(1, 60) as $n) {
        BackupData::simpleSale();
    }
    config(['backup.transactions.max_attachment_mb' => 0.02]);

    $backup = app(TransactionBackupService::class)->export();
    $mail = new TransactionBackupMail($backup);

    $mail->assertHasAttachedData($backup->attachmentData, 'lutong-fiesta-transactions-20261027-230003.json.gz', ['mime' => 'application/gzip']);
    $mail->assertSeeInText('compressed with gzip');
    $mail->assertSeeInText('Transactions:  60');
});

it('describes an empty history', function () {
    $mail = new TransactionBackupMail(app(TransactionBackupService::class)->export());

    $mail->assertSeeInText('Covers: no transactions yet');
    $mail->assertSeeInText('Transactions:  0');
});
