<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $servicio)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->servicio->login($request->input('usuario'), $request->input('password'));

        return match ($resultado) {
            AuthService::CREDENCIALES_INVALIDAS => $this->error('Usuario o contraseña incorrectos.', 401),
            AuthService::USUARIO_INACTIVO => $this->error('Tu cuenta está desactivada. Comunícate con el administrador.', 403),
            default => $this->exito($resultado, 'Sesión iniciada.'),
        };
    }

    public function me(Request $request): JsonResponse
    {
        return $this->exito($this->servicio->perfil($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->servicio->logout($request->user());

        return $this->exito(null, 'Sesión cerrada.');
    }

    private function exito(mixed $data, string $mensaje = 'OK', int $estado = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $mensaje,
        ], $estado);
    }

    private function error(string $mensaje, int $estado): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $mensaje,
            'errors' => (object) [],
        ], $estado);
    }
}
