<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function store(Request $request, AuthService $auth): RedirectResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $resultado = $auth->login($datos['usuario'], $datos['password']);

        if (is_string($resultado)) {
            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contraseña incorrectos, o cuenta desactivada.',
            ]);
        }

        $request->session()->regenerate();

        $usuario = Auth::guard('web')->user();
        if ($usuario?->rol === 'admin') {
            return redirect()->route('admin.inicio');
        }
        if ($usuario?->rol === 'docente') {
            return redirect()->route('docente.panel');
        }
        if ($usuario?->rol === 'estudiante' && config('cni.alumno_areas_enabled')) {
            // Solo la primera vez se muestra la selección de las cinco áreas.
            return $usuario->area_postulacion_id === null && $usuario->carrera_id === null
                ? redirect()->route('alumno.elegir-area')
                : redirect()->route('alumno.panel');
        }

        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request, AuthService $auth): RedirectResponse
    {
        $auth->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
