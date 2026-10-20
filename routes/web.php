<?php

use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureActiveUser;
use App\Livewire\Panel;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('inicio');

Route::middleware('guest:web')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth:web')->name('logout');

Route::get('/panel', Panel::class)
    ->middleware(['auth:web', EnsureActiveUser::class])->name('panel');

// Compatibilidad con los módulos existentes: JSON con sesión y CSRF.
// Se cargan dentro de web; no existe un segundo sistema de autenticación.
Route::prefix('api')->group(base_path('routes/api.php'));
