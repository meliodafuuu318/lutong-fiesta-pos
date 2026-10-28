# Daily transaction backups

Every day at **11:00 PM Philippines time (15:00 UTC)** the app emails a JSON export of the **whole**
transaction history to a recovery mailbox. It is a full export each time, not an incremental one, so
any single email is enough to rebuild the transactions.

It only reads the database. It never changes or deletes records, and it does not write files (the
app runs on Vercel, where there is no persistent disk): the export is built in memory and attached to
the email.

## What is in the email

* **Attachment** `lutong-fiesta-transactions-YYYYMMDD-HHMMSS.json` (Manila time). If the JSON is
  bigger than the attachment limit it is gzipped (`....json.gz`) and the email says so.
* **Body**: when it was exported, the date range covered, the record counts, the file size and the
  file's SHA-256 (so you can check your download). No transaction contents are in the body.

### File format (version 1)

```json
{
  "manifest": {
    "format": "lutong-fiesta-transaction-backup",
    "format_version": 1,
    "backup_type": "full",
    "application": "Lutong-Fiesta-POS",
    "exported_at": "2026-10-27T23:00:03+08:00",
    "exported_at_utc": "2026-10-27T15:00:03Z",
    "timezone": "Asia/Manila",
    "record_counts": { "transactions": 0, "transaction_items": 0, "transaction_payments": 0, "deliveries": 0 },
    "date_range": { "basis": "transactions.transaction_date", "from": null, "to": null },
    "highest_transaction_id": null,
    "notes": ["..."]
  },
  "transactions": [
    {
      "id": 1, "reference_number": "...", "type": "WALK IN|DELIVERY",
      "status": "...", "payment_status": "...", "total": "265.00",
      "transaction_date": "...", "created_at": "...", "updated_at": "...",
      "delivery": { "id": 1, "address": "...", "status": "...", "created_at": "...", "updated_at": "..." },
      "items": [{
        "id": 1, "daily_item_id": 1, "quantity": 2, "unit_price": "120.00", "subtotal": "240.00",
        "created_at": "...", "updated_at": "...",
        "daily_item": { "id": 1, "item_id": 1, "date": "2026-10-27", "price": "120.00", "status": "AVAILABLE" },
        "item": { "id": 1, "name": "Adobo", "category_id": 1, "category": "Mains" }
      }],
      "payments": [{
        "id": 1, "payment_method": "CASH", "payment_amount": "300.00",
        "total_balance": "265.00", "outstanding_balance": "-35.00", "created_at": "...", "updated_at": "..."
      }]
    }
  ]
}
```

* IDs are the database IDs. Timestamps are ISO 8601 with the Manila offset. Money is a string with two
  decimals, so nothing is rounded.
* `delivery` is `null` for walk in transactions. `quantity` is `null` for a line sold at a custom price.
* Cancelled and refunded transactions are included, with their `CANCELLED`/`REFUNDED` statuses on the
  transaction, its `payment_status` and its delivery.

### What the database does not keep (so the backup can't either)

* **Item names are not saved on each transaction line.** The export reads the name from the `items`
  table when it runs, so an item renamed later appears under its new name in later backups (older
  backups still have the old name). The price saved with the line (`unit_price`, `subtotal`) is
  exact; `daily_item.price` is that day's menu price.
* **There are no discount or refund-amount columns.** Discounts are not in the export because they
  are not in the schema. A custom price shows as a `unit_price` different from `daily_item.price`.
* **Only the transaction history is backed up**, not users, expenses, the full menu or items that were
  never sold. See "Recovery" below.

## Configuration

Set these in the Vercel project (Settings → Environment Variables, **Production**). Locally, put them
in `.env`. `.env.example` has placeholders only; never commit real values.

| Variable | Required | What it is |
|---|---|---|
| `TRANSACTION_BACKUP_EMAIL` | yes | The recovery mailbox. |
| `CRON_SECRET` | yes, on Vercel | A random secret of **at least 32 characters** (`openssl rand -hex 32`). Vercel Cron sends it as `Authorization: Bearer <secret>`; the endpoint refuses every other request. |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | yes | The app's normal mail settings, which the backup reuses. |
| `TRANSACTION_BACKUP_MAX_ATTACHMENT_MB` | no (15) | Largest attachment, in MB of the file. |
| `TRANSACTION_BACKUP_LOCK_STORE` | no (`database`) | Cache store for the "already running" lock. |

### Mail

Use a real mailer such as `smtp`. The command **refuses to run** with `MAIL_MAILER=log` or `array`, or
with `failover`/`roundrobin` when one of its mailers is `log`/`array`: those would "send" the whole
history into the logs, or nowhere, and still look successful. (`--dry-run` doesn't need a mailer.)

Choose a recovery mailbox that does **not** depend on the hosting or database providers (not an
address on a domain hosted by the same account, and not the database provider's notification address),
and protect it with two-factor sign-in: the file contains customer delivery addresses and sales.

Use an SMTP port of 587 or 465: serverless hosts commonly block outbound port 25.

The attachment is sent as MIME, which makes it about 37% bigger. Most mailboxes refuse messages over
20 to 25 MB, so the default limit is 15 MB. Over the limit, the backup is gzipped (JSON usually
compresses to a tenth of its size or less). If it is still too big the backup **fails** and says so; it is
never truncated. Raise the limit only if your mailbox accepts larger messages.

## Scheduling on Vercel

Laravel's scheduler (`schedule:run`) doesn't run by itself on Vercel, so the schedule is a Vercel Cron
job, in `vercel.json`:

```json
"crons": [{ "path": "/api/cron/transaction-backup", "schedule": "0 15 * * *" }]
```

* Vercel Cron schedules are in **UTC**: 15:00 UTC is 23:00 in Manila (no daylight saving).
* Vercel calls the path on the **production** deployment with `GET` and, when the `CRON_SECRET`
  environment variable exists, adds `Authorization: Bearer <CRON_SECRET>`. The route (`api/cron/...`,
  middleware `VerifyCronSecret`) compares it in constant time. It answers **401** for a missing or
  wrong token, and **503** if the secret isn't set or is shorter than 32 characters (it never opens
  up when misconfigured). The secret is only read from the header, never from the URL, and is not logged.
* The endpoint runs the same `backup:transactions` command and only answers with a status: `200 ok`,
  `409` already running, `500` failed. Details (never transaction contents or credentials) are in the logs.
* `vercel.json` also sets `maxDuration: 60` on the PHP function, so a large export isn't cut off at the
  default limit. Check that your plan allows it (Project → Settings → Functions).
* On the **Hobby** plan a daily cron may fire any time within the scheduled hour, so expect the email
  between 11:00 PM and 11:59 PM Manila time. Pro plans run at the exact minute.

### Setup checklist

1. Add the environment variables above to the Production environment, with `MAIL_*` pointing at a
   working SMTP account.
2. Deploy to production (crons only exist on production deployments).
3. Project → Settings → Cron Jobs: confirm `/api/cron/transaction-backup` at `0 15 * * *` is listed.
4. Trigger it once now (see below) and check the email arrives.
5. Check the next morning that the nightly email arrived.

## Running it by hand

```bash
php artisan backup:transactions --dry-run   # builds and checks the backup, emails nothing
php artisan backup:transactions             # builds, checks and emails it
```

| Exit code | Meaning |
|---|---|
| 0 | Sent (or the dry run worked) |
| 1 | Failed: configuration, export, size limit or email. See the message and the logs |
| 2 | Skipped: another backup is running |

Two backups never run at once (a lock in the database cache; if the lock can't be used the backup still
runs). The lock expires after 15 minutes, so a crashed run can't block tomorrow's.

On Vercel there is no shell, so trigger it through the endpoint. Keep the secret out of the command
history by reading it from an environment variable:

```bash
curl -i -H "Authorization: Bearer $CRON_SECRET" https://<your-production-domain>/api/cron/transaction-backup
```

The Cron Jobs page in the Vercel dashboard may also offer a way to run the job (depending on your plan).

## Checking that it works

Automated tests (SQLite in memory, mail faked, no real database or email):

```bash
php artisan test tests/Feature/TransactionBackup
```

In production, "sent" means the mail server **accepted** the message, not that it reached the inbox.
Only the arrival of the email proves that, so look for it. A failure shows up as a missing email and
as a `500` cron run with `Transaction backup failed` in the Vercel logs (the `stage` field says
whether it was configuration, export, or email). Consider a mail rule that alerts you when no backup
email has arrived in 26 hours.

After downloading a backup, verify it:

```bash
sha256sum lutong-fiesta-transactions-*.json        # compare with the SHA-256 in the email body
python3 -m json.tool lutong-fiesta-transactions-*.json > /dev/null && echo valid
```

(For a `.json.gz` file, hash the `.gz` as downloaded, then `gunzip` it.)

## Recovery

There is no automatic restore. The file is a complete, readable record you can load with a script:

1. Re-create the menu rows the transactions point to. Each line carries what is needed to rebuild
   the `categories`, `items` and `daily_items` it references (`item.id/name/category_id/category`,
   `daily_item.id/item_id/date/price/status`). `items.default_price` and `items.is_active` are not in
   the export; set them yourself.
2. Insert `transactions`, then `transaction_items`, `transaction_payments` and `deliveries` using the
   IDs in the file, so references stay intact.
3. Compare the row counts with `manifest.record_counts`.

Users, expenses and the rest of the menu are not part of this backup.

Considerations:

* Restoring over live data can duplicate or overwrite rows. Restore into an empty database.
* Keep several days of backups. A bad day (for example, records deleted by mistake) is only visible
  by comparing against an older one, and each email is a full copy.
* The mailbox is part of your security perimeter: anyone who can read it can read the customer
  addresses. Rotate `CRON_SECRET` (change the variable and redeploy) if it may have leaked.
* Memory use is roughly three times the size of the JSON. Measured on a 30,000 transaction history
  (44 MB of JSON, 90,000 lines), a run took about 123 MB of PHP memory.
