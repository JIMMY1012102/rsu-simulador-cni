<?php

namespace App\Providers;

use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Ambas entradas de login comparten los mismos límites.
        RateLimiter::for('login', function (Request $request) {
            $usuario = Str::lower((string) $request->input('usuario'));

            return [
                Limit::perMinute(10)->by('login:'.$usuario.'|'.$request->ip()),
                Limit::perMinute(60)->by('login-ip:'.$request->ip()),
            ];
        });

        Livewire::addPersistentMiddleware([EnsureActiveUser::class]);
    }
}
