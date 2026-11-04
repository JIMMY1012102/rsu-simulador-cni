<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('web')?->rol === 'admin', 403);
        abort_unless(config('cni.administracion_academica_enabled'), 404);

        return $next($request);
    }
}
