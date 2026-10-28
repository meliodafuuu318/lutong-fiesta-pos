<?php

namespace App\Http\Controllers;

use App\Console\Commands\BackupTransactions;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Vercel Cron's way in. It runs the same command as the command line, and only
 * says whether it worked: the details (counts, errors) are in the logs, never in
 * the response.
 */
class TransactionBackupCronController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return match (Artisan::call('backup:transactions')) {
            Command::SUCCESS => response()->json(['status' => 'ok']),
            BackupTransactions::EXIT_SKIPPED => response()->json(['status' => 'skipped', 'message' => 'A backup is already running.'], 409),
            default => response()->json(['status' => 'failed', 'message' => 'The backup failed. See the application logs.'], 500),
        };
    }
}
