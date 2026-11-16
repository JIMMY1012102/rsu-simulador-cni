<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Respuestas JSON para el mismo navegador y la misma sesión web. */
class AuthController extends Controller
{
    public function __construct(private AuthService $servicio) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->servicio->login($request->input('usuario'), $request->input('password'));

        if (is_string($resultado)) {
            return response()->json([
                'success' => false, 'data' => null,
                'message' => 'Usuario o contraseña incorrectos, o cuenta desactivada.',
                'errors' => (object) [],
            ], 401);
        }

        $request->session()->regenerate();

        return $this->exito($resultado, 'Sesión iniciada.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->exito($this->servicio->perfil($request->user('web')));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->servicio->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->exito(null, 'Sesión cerrada.');
    }

    private function exito(mixed $data, string $mensaje = 'OK'): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $mensaje]);
    }
}
