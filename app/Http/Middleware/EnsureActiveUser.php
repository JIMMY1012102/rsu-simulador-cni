<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('web');

        abort_unless($usuario && $usuario->activo
            && in_array($usuario->rol, Usuario::ROLES, true), 403, 'Cuenta sin acceso al sistema.');

        return $next($request);
    }
}
