<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

/**
 * Lógica del módulo de autenticación: validar credenciales,
 * emitir y revocar tokens de Sanctum, y armar el perfil público.
 */
class AuthService
{
    public const CREDENCIALES_INVALIDAS = 'credenciales_invalidas';

    public const USUARIO_INACTIVO = 'usuario_inactivo';

    /**
     * Devuelve ['token' => ..., 'usuario' => ...] o el código de error.
     */
    public function login(string $usuario, string $password): array|string
    {
        $registro = Usuario::where('usuario', $usuario)->first();

        if (! $registro || ! Hash::check($password, $registro->password)) {
            return self::CREDENCIALES_INVALIDAS;
        }

        if (! $registro->activo) {
            return self::USUARIO_INACTIVO;
        }

        return [
            'token' => $registro->createToken('api')->plainTextToken,
            'tipo_token' => 'Bearer',
            'usuario' => $this->perfil($registro),
        ];
    }

    /**
     * Revoca solo el token usado en esta petición (las otras sesiones siguen activas).
     */
    public function logout(Usuario $usuario): void
    {
        $usuario->currentAccessToken()?->delete();
    }

    /**
     * Datos públicos del usuario; nunca incluye la contraseña.
     */
    public function perfil(Usuario $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombres' => $usuario->nombres,
            'apellidos' => $usuario->apellidos,
            'usuario' => $usuario->usuario,
            'rol' => $usuario->rol,
            'seccion_id' => $usuario->seccion_id,
            'carrera_id' => $usuario->carrera_id,
        ];
    }
}
