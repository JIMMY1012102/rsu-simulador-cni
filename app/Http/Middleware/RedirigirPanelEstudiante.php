<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirigirPanelEstudiante
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('cni.alumno_areas_enabled') && $request->user('web')?->rol === 'estudiante') {
            return redirect()->route('alumno.panel');
        }
        return $next($request);
    }
}
