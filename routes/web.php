<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DailyItemController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExpenseController;
use App\Http\Controllers\Web\ItemController;
use App\Http\Controllers\Web\MenuController;
use App\Http\Controllers\Web\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Laravel web route works!', 200);
});

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/menu', [MenuController::class, 'index'])->name('menu');
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::patch('/items/{item}/active', [ItemController::class, 'toggleActive'])->name('items.toggle-active');
    Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/payments', [TransactionController::class, 'addPayment'])->name('transactions.payments.store');
    Route::patch('/transactions/{transaction}/delivery', [TransactionController::class, 'markDelivered'])->name('transactions.deliver');
    Route::patch('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::patch('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('/daily-items', [DailyItemController::class, 'store'])->name('daily-items.store');
    Route::put('/daily-items/{dailyItem}', [DailyItemController::class, 'update'])->name('daily-items.update');
    Route::delete('/daily-items/{dailyItem}', [DailyItemController::class, 'destroy'])->name('daily-items.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
