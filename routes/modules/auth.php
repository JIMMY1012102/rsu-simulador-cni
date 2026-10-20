<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])
    ->middleware(['guest:web', 'throttle:login'])->name('auth.login');
Route::post('logout', [AuthController::class, 'logout'])
    ->middleware('auth:web')->name('auth.logout');
Route::get('me', [AuthController::class, 'me'])
    ->middleware(['auth:web', EnsureActiveUser::class])->name('auth.me');
