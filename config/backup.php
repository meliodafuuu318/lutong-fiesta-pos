<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daily transaction backup
    |--------------------------------------------------------------------------
    |
    | `php artisan backup:transactions` emails a complete JSON export of the
    | transaction history. On Vercel it is started once a day by Vercel Cron
    | through /api/cron/transaction-backup (see vercel.json and
    | docs/transaction-backups.md).
    |
    */

    'transactions' => [

        // The recovery mailbox. Use one that does not depend on the hosting or
        // database providers, so the backup is still reachable if either is lost.
        'email' => env('TRANSACTION_BACKUP_EMAIL'),

        // Shared secret Vercel Cron sends as "Authorization: Bearer <secret>".
        // Vercel fills the CRON_SECRET environment variable in itself once you add it.
        'cron_secret' => env('CRON_SECRET'),

        // Largest attachment to send, in megabytes of the file itself. Email
        // encodes attachments (about 1.37 times bigger), and most mailboxes refuse
        // messages over 20 to 25 MB, so the default leaves some room. Over this the
        // backup is compressed; if it is still over, it fails. It is never truncated.
        'max_attachment_mb' => (float) env('TRANSACTION_BACKUP_MAX_ATTACHMENT_MB', 15),

        // Timestamps in the export and the file name use this timezone (15:00 UTC is 23:00 here).
        'timezone' => 'Asia/Manila',

        // Cache store holding the lock that stops two backups from running at once.
        // It has to be shared between requests, so not "array" (which is what
        // vercel.json sets for the app's default cache).
        'lock_store' => env('TRANSACTION_BACKUP_LOCK_STORE', 'database'),

    ],

];
