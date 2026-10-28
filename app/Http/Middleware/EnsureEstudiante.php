<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Solo deja pasar a los alumnos (rol "estudiante") a las páginas del portal del alumno. */
class EnsureEstudiante
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('web')?->rol === 'estudiante', 403, 'Esta sección es solo para alumnos.');

        return $next($request);
    }
}
