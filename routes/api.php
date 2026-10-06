<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ItemController
};

Route::post('auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('items', ItemController::class);
});