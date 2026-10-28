<?php

use App\Http\Controllers\TransactionBackupCronController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cron routes
|--------------------------------------------------------------------------
|
| Started by Vercel Cron (see vercel.json). Every route here is behind the
| `cron.secret` middleware (see bootstrap/app.php) and is stateless.
|
| These are deliberately NOT under /api. On Vercel the PHP function is the file
| api/index.php, so Laravel takes "/api" for the directory of the front controller
| and removes it from the path: a request for /api/cron/x is matched as /cron/x
| and gets a 404. Nothing under /api/ can be routed there.
|
*/

Route::match(['get', 'post'], 'cron/transaction-backup', TransactionBackupCronController::class)
    ->name('cron.transaction-backup');
