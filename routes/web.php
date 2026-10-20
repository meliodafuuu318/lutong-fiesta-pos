<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DailyItemController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::post('/daily-items', [DailyItemController::class, 'store'])->name('daily-items.store');
    Route::put('/daily-items/{dailyItem}', [DailyItemController::class, 'update'])->name('daily-items.update');
    Route::delete('/daily-items/{dailyItem}', [DailyItemController::class, 'destroy'])->name('daily-items.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
