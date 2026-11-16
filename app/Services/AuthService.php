<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

/** Autenticación web única: sesión de Laravel, sin emitir tokens de API. */
class AuthService
{
    public const CREDENCIALES_INVALIDAS = 'credenciales_invalidas';

    public function login(string $usuario, string $password): array|string
    {
        if (! Auth::guard('web')->attempt([
            'usuario' => $usuario,
            'password' => $password,
            'activo' => true,
            'rol' => Usuario::ROLES,
        ])) {
            return self::CREDENCIALES_INVALIDAS;
        }

        return ['usuario' => $this->perfil(Auth::guard('web')->user())];
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
    }

    public function perfil(Usuario $usuario): array
    {
        return $usuario->only([
            'id', 'nombres', 'apellidos', 'usuario', 'rol', 'seccion_id', 'carrera_id',
        ]);
    }
}
