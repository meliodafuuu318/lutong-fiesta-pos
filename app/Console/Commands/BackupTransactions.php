<?php

namespace App\Console\Commands;

use App\Exceptions\BackupException;
use App\Mail\TransactionBackupMail;
use App\Services\TransactionBackupService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BackupTransactions extends Command
{
    /** Another backup is already running, so this one did nothing. */
    public const EXIT_SKIPPED = 2;

    private const LOCK_NAME = 'transaction-backup';

    /** Longer than any single run can last on Vercel, so a crashed run can't block the next day's. */
    private const LOCK_SECONDS = 900;

    protected $signature = 'backup:transactions
        {--dry-run : Build and check the backup, but do not email it}';

    protected $description = 'Email a complete JSON export of all transactions to the recovery mailbox';

    /**
     * Exit codes: 0 sent (or a dry run that worked), 1 failed, 2 skipped because another backup is running.
     */
    public function handle(TransactionBackupService $backups): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $recipient = null;

        if (! $dryRun) {
            try {
                $recipient = $this->recipient();
                $this->ensureMailDelivers();
            } catch (BackupException $e) {
                return $this->failed('configuration', $e);
            }
        }

        [$lock, $acquired] = $this->acquireLock();

        if (! $acquired) {
            $this->warn('Another transaction backup is already running, so this one was skipped.');
            Log::warning('Transaction backup skipped: another one is already running.');

            return self::EXIT_SKIPPED;
        }

        try {
            return $this->backUp($backups, $recipient, $dryRun);
        } finally {
            $lock?->release();
        }
    }

    private function backUp(TransactionBackupService $backups, ?string $recipient, bool $dryRun): int
    {
        try {
            $backup = $backups->export();
        } catch (Throwable $e) {
            return $this->failed('export', $e);
        }

        $counts = $backup->counts();
        $this->line(sprintf(
            'Exported %d transactions, %d items, %d payments and %d deliveries (%s%s).',
            $counts['transactions'],
            $counts['transaction_items'],
            $counts['transaction_payments'],
            $counts['deliveries'],
            number_format($backup->attachmentBytes() / 1024, 1).' KB',
            $backup->compressed ? ', compressed' : '',
        ));

        if ($dryRun) {
            $this->info('Dry run: the backup is valid and nothing was emailed.');

            return self::SUCCESS;
        }

        try {
            Mail::to($recipient)->send(new TransactionBackupMail($backup));
        } catch (Throwable $e) {
            return $this->failed('email', $e);
        }

        // "Sent" means the mail server accepted it. It doesn't prove it reached the inbox.
        Log::info('Transaction backup emailed.', [
            'transactions' => $counts['transactions'],
            'attachment_bytes' => $backup->attachmentBytes(),
            'compressed' => $backup->compressed,
        ]);
        $this->info('Backup sent to the recovery mailbox ('.$backup->attachmentName.').');

        return self::SUCCESS;
    }

    /** @throws BackupException */
    private function recipient(): string
    {
        $email = trim((string) config('backup.transactions.email'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BackupException('TRANSACTION_BACKUP_EMAIL is not set, or is not a valid email address.');
        }

        return $email;
    }

    /**
     * A mailer that only writes to the log or keeps mail in memory would "send" the
     * whole transaction history nowhere (or into the logs) and still look successful.
     *
     * @throws BackupException
     */
    private function ensureMailDelivers(): void
    {
        $mailer = (string) config('mail.default');

        if ($this->deliversNowhere($mailer)) {
            throw new BackupException(sprintf(
                'MAIL_MAILER is "%s", which does not deliver email (or falls back to something that does not). Configure a real mailer, such as smtp, for the backup.',
                $mailer,
            ));
        }
    }

    private function deliversNowhere(string $mailer, int $depth = 0): bool
    {
        $transport = config("mail.mailers.{$mailer}.transport", $mailer);

        if (in_array($transport, ['log', 'array'], true)) {
            return true;
        }

        if ($depth < 3 && in_array($transport, ['failover', 'roundrobin'], true)) {
            foreach ((array) config("mail.mailers.{$mailer}.mailers") as $inner) {
                if ($this->deliversNowhere((string) $inner, $depth + 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Stops two backups running at once. The lock is kept in the database, because
     * every Vercel request is a separate process. If the lock itself can't be used,
     * the backup goes ahead: a missing lock is a lesser problem than a missing backup.
     *
     * @return array{0: Lock|null, 1: bool}
     */
    private function acquireLock(): array
    {
        try {
            $lock = Cache::store(config('backup.transactions.lock_store'))->lock(self::LOCK_NAME, self::LOCK_SECONDS);

            return [$lock, $lock->get()];
        } catch (Throwable $e) {
            Log::warning('Transaction backup lock is unavailable; continuing without it.', ['error' => $e::class]);

            return [null, true];
        }
    }

    /**
     * Failures go to the console and the log. Our own exceptions have safe messages;
     * for anything else (a query, the mail server) only the class is written, because
     * those messages can contain SQL values, addresses or server details.
     */
    private function failed(string $stage, Throwable $e): int
    {
        $detail = $e instanceof BackupException ? $e->getMessage() : $e::class;

        Log::error('Transaction backup failed.', ['stage' => $stage, 'error' => $detail]);
        $this->error("Transaction backup failed ({$stage}): {$detail}");

        return self::FAILURE;
    }
}
