<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * "Mi perfil": el alumno ve sus datos y puede cambiar su nombre, apellidos y contraseña.
 * El usuario de acceso, la sección y la carrera los asigna el colegio y no se editan aquí.
 */
class PerfilController extends Controller
{
    public function show(): View
    {
        /** @var Usuario $usuario */
        $usuario = auth('web')->user();
        $usuario->loadMissing(['carrera', 'seccion']);

        return view('alumno.perfil', ['usuario' => $usuario]);
    }

    public function actualizarDatos(Request $request): RedirectResponse
    {
        // El proyecto no tiene lang/es: los mensajes van aquí en español.
        $datos = $request->validateWithBag('datos', [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'max' => 'Máximo :max caracteres.',
        ]);

        $request->user('web')->update([
            'nombres' => trim($datos['nombres']),
            'apellidos' => trim($datos['apellidos']),
        ]);

        return redirect()->route('alumno.perfil')->with('status', 'Tus datos se guardaron correctamente.');
    }

    public function actualizarPassword(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('password', [
            'password_actual' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'required' => 'Este campo es obligatorio.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La nueva contraseña debe tener al menos una letra.',
            'password.numbers' => 'La nueva contraseña debe tener al menos un número.',
        ]);

        // El cast "hashed" del modelo Usuario guarda la contraseña cifrada.
        $request->user('web')->update(['password' => $datos['password']]);
        $request->session()->regenerate();

        return redirect()->route('alumno.perfil')->with('status', 'Tu contraseña se cambió correctamente.');
    }
}
