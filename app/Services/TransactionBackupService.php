<?php

namespace App\Services;

use App\Exceptions\BackupException;
use App\Models\Delivery;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Builds the complete JSON export of the transaction history.
 *
 * Read only: it never writes to or deletes from the database, and it keeps
 * everything in memory (no files), because the app runs on Vercel.
 */
class TransactionBackupService
{
    public const FORMAT = 'lutong-fiesta-transaction-backup';

    public const FORMAT_VERSION = 1;

    /** Transactions loaded at a time, so memory stays flat however long the history is. */
    private const CHUNK_SIZE = 200;

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    /**
     * Export every transaction, with its items, payments and delivery.
     *
     * @throws BackupException when the export can't be completed, or is too big to email
     */
    public function export(?CarbonInterface $at = null): TransactionBackup
    {
        $at = ($at ?? now())->copy()->setTimezone($this->timezone());

        try {
            // One transaction = one consistent snapshot of the database for every query below
            // (it only reads). Without it a payment recorded mid-export could be missed.
            [$json, $manifest] = DB::transaction(fn () => $this->build($at));
        } catch (JsonException) {
            throw new BackupException('The transactions could not be encoded as JSON.');
        }

        $this->validate($json);

        return $this->attachment($json, $manifest, $at);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function build(CarbonInterface $at): array
    {
        $highestId = (int) Transaction::query()->max('id');

        $counts = [
            'transactions' => 0,
            'transaction_items' => 0,
            'transaction_payments' => 0,
            'deliveries' => 0,
        ];
        $first = null;
        $last = null;
        $transactions = '';
        $separator = '';

        if ($highestId > 0) {
            Transaction::query()
                ->where('id', '<=', $highestId)
                ->with([
                    'transactionItems' => fn ($query) => $query->orderBy('id'),
                    'transactionItems.dailyItem.item.category',
                    'transactionPayments' => fn ($query) => $query->orderBy('id'),
                    'delivery',
                ])
                ->chunkById(self::CHUNK_SIZE, function ($chunk) use (&$transactions, &$separator, &$counts, &$first, &$last) {
                    foreach ($chunk as $transaction) {
                        $transactions .= $separator.json_encode($this->transaction($transaction), self::JSON_FLAGS);
                        $separator = ',';

                        $counts['transactions']++;
                        $counts['transaction_items'] += $transaction->transactionItems->count();
                        $counts['transaction_payments'] += $transaction->transactionPayments->count();
                        $counts['deliveries'] += $transaction->delivery ? 1 : 0;

                        $first = ($first === null || $transaction->transaction_date->lt($first)) ? $transaction->transaction_date : $first;
                        $last = ($last === null || $transaction->transaction_date->gt($last)) ? $transaction->transaction_date : $last;
                    }
                });
        }

        $this->assertMatchesDatabase($counts, $highestId);

        $manifest = [
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'backup_type' => 'full',
            'application' => config('app.name'),
            'exported_at' => $at->toIso8601String(),
            'exported_at_utc' => $at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'timezone' => $this->timezone(),
            'record_counts' => $counts,
            'date_range' => [
                'basis' => 'transactions.transaction_date',
                'from' => $first ? $this->iso($first) : null,
                'to' => $last ? $this->iso($last) : null,
            ],
            'highest_transaction_id' => $highestId ?: null,
            'notes' => [
                'This is a complete export of every transaction, not an incremental one.',
                'Item names are read from the items table when the export runs. The database does not keep a copy of the name on each transaction line, so an item renamed later shows its new name here. unit_price and subtotal are the values saved with each line; daily_item.price is that day\'s menu price.',
                'A line with a null quantity was sold at a custom unit_price.',
                'The database has no discount or refund amount columns. Cancellations and refunds are the status values on the transaction, its payment (payment_status) and its delivery.',
            ],
        ];

        $json = '{"manifest":'.json_encode($manifest, self::JSON_FLAGS).',"transactions":['.$transactions.']}';

        return [$json, $manifest];
    }

    /** @return array<string, mixed> */
    private function transaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'reference_number' => $transaction->reference_number,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'total' => $transaction->total,
            'transaction_date' => $this->iso($transaction->transaction_date),
            'created_at' => $this->iso($transaction->created_at),
            'updated_at' => $this->iso($transaction->updated_at),
            'delivery' => $transaction->delivery ? [
                'id' => $transaction->delivery->id,
                'address' => $transaction->delivery->address,
                'status' => $transaction->delivery->status,
                'created_at' => $this->iso($transaction->delivery->created_at),
                'updated_at' => $this->iso($transaction->delivery->updated_at),
            ] : null,
            'items' => $transaction->transactionItems->map(fn (TransactionItem $line) => [
                'id' => $line->id,
                'daily_item_id' => $line->daily_item_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'subtotal' => $line->subtotal,
                'created_at' => $this->iso($line->created_at),
                'updated_at' => $this->iso($line->updated_at),
                'daily_item' => $line->dailyItem ? [
                    'id' => $line->dailyItem->id,
                    'item_id' => $line->dailyItem->item_id,
                    'date' => $line->dailyItem->date?->toDateString(),
                    'price' => $line->dailyItem->price,
                    'status' => $line->dailyItem->status,
                ] : null,
                'item' => $line->dailyItem?->item ? [
                    'id' => $line->dailyItem->item->id,
                    'name' => $line->dailyItem->item->name,
                    'category_id' => $line->dailyItem->item->category_id,
                    'category' => $line->dailyItem->item->category?->name,
                ] : null,
            ])->all(),
            'payments' => $transaction->transactionPayments->map(fn (TransactionPayment $payment) => [
                'id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'payment_amount' => $payment->payment_amount,
                'total_balance' => $payment->total_balance,
                'outstanding_balance' => $payment->outstanding_balance,
                'created_at' => $this->iso($payment->created_at),
                'updated_at' => $this->iso($payment->updated_at),
            ])->all(),
        ];
    }

    /**
     * The number of records written has to equal what the database holds for the
     * same transactions (read in the same snapshot), or something was skipped.
     *
     * @param  array<string, int>  $exported
     *
     * @throws BackupException
     */
    private function assertMatchesDatabase(array $exported, int $highestId): void
    {
        $inDatabase = [
            'transactions' => Transaction::query()->where('id', '<=', $highestId)->count(),
            'transaction_items' => TransactionItem::query()->where('transaction_id', '<=', $highestId)->count(),
            'transaction_payments' => TransactionPayment::query()->where('transaction_id', '<=', $highestId)->count(),
            'deliveries' => Delivery::query()->where('transaction_id', '<=', $highestId)->count(),
        ];

        if ($exported !== $inDatabase) {
            throw new BackupException('The export does not match the database record counts, so it was discarded.');
        }
    }

    /** @throws BackupException */
    private function validate(string $json): void
    {
        if (! json_validate($json)) {
            throw new BackupException('The generated backup is not valid JSON.');
        }
    }

    /**
     * Pick what to attach. Over the size limit it is compressed, and over the
     * limit even then it is refused. It is never cut short.
     *
     * @param  array<string, mixed>  $manifest
     *
     * @throws BackupException
     */
    private function attachment(string $json, array $manifest, CarbonInterface $at): TransactionBackup
    {
        $name = 'lutong-fiesta-transactions-'.$at->format('Ymd-His').'.json';
        $limit = $this->limitBytes();
        $size = strlen($json);

        if ($size <= $limit) {
            return new TransactionBackup($manifest, $name, $json, false, $size);
        }

        if (! function_exists('gzencode')) {
            throw new BackupException(sprintf(
                'The backup is %s, over the %s attachment limit, and this PHP has no zlib to compress it. Nothing was sent.',
                $this->megabytes($size),
                $this->megabytes($limit),
            ));
        }

        $compressed = gzencode($json, 9);

        if ($compressed === false || strlen($compressed) > $limit) {
            throw new BackupException(sprintf(
                'The backup is %s (%s compressed), over the %s attachment limit. Nothing was sent. Raise TRANSACTION_BACKUP_MAX_ATTACHMENT_MB if the mailbox accepts bigger messages.',
                $this->megabytes($size),
                $compressed === false ? 'could not be' : $this->megabytes(strlen($compressed)),
                $this->megabytes($limit),
            ));
        }

        return new TransactionBackup($manifest, $name.'.gz', $compressed, true, $size);
    }

    private function limitBytes(): int
    {
        $megabytes = (float) config('backup.transactions.max_attachment_mb');

        return (int) floor(($megabytes > 0 ? $megabytes : 15) * 1024 * 1024);
    }

    private function megabytes(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 2).' MB';
    }

    private function timezone(): string
    {
        return (string) config('backup.transactions.timezone', 'Asia/Manila');
    }

    private function iso(?CarbonInterface $date): ?string
    {
        return $date?->copy()->setTimezone($this->timezone())->toIso8601String();
    }
}
