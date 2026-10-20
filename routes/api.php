<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ItemController,
    DailyItemController,
    TransactionController
};

Route::name('api.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('items', ItemController::class);

        Route::get('daily-items', [DailyItemController::class, 'index']);
        Route::post('daily-items', [DailyItemController::class, 'addDailyItem']);
        Route::delete('daily-items/{dailyItem}', [DailyItemController::class, 'removeDailyItem']);
        Route::put('daily-items/{dailyItem}', [DailyItemController::class, 'updateDailyItem']);

        Route::apiResource('transactions', TransactionController::class);
        Route::put('transactions/{transaction}/cancel', [TransactionController::class, 'cancelTransaction']);
        Route::put('transactions/{transaction}/refund', [TransactionController::class, 'refundTransaction']);
    });
});