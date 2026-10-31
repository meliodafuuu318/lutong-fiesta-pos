<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ItemController,
    DailyItemController,
    TransactionController,
    DashboardController,
    ExpenseController
};

Route::name('api.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::apiResource('items', ItemController::class);

        Route::get('daily-items', [DailyItemController::class, 'index'])->name('daily-items.index');
        Route::get('daily-items/all', [DailyItemController::class, 'tempGetAllDailyItems'])->name('daily-items.all');
        Route::post('daily-items', [DailyItemController::class, 'addDailyItem'])->name('daily-items.add');
        Route::delete('daily-items/{dailyItem}', [DailyItemController::class, 'removeDailyItem'])->name('daily-items.remove');
        Route::put('daily-items/{dailyItem}', [DailyItemController::class, 'updateDailyItem'])->name('daily-items.update');

        Route::apiResource('transactions', TransactionController::class);
        Route::put('transactions/{transaction}/cancel', [TransactionController::class, 'cancelTransaction'])->name('transactions.cancel');
        Route::put('transactions/{transaction}/refund', [TransactionController::class, 'refundTransaction'])->name('transactions.refund');

        Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);
    });
});