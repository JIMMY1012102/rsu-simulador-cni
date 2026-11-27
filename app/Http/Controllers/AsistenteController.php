<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AsistenteController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!config('asistente.enabled')) {
            return response()->json([
                'respuesta' => 'El asistente está desactivado en este momento. Inténtelo nuevamente más tarde.',
            ], 503);
        }

        $datos = $request->validate([
            'mensaje' => ['required', 'string', 'min:1', 'max:500'],
            'sesion' => ['nullable', 'string', 'max:120'],
        ]);
        $usuario = $request->user('web');
        abort_unless($usuario && in_array($usuario->rol, ['estudiante', 'admin'], true), 403);

        try {
            $respuesta = Http::acceptJson()
                ->withHeader('X-Agente-Token', (string) config('asistente.token'))
                ->timeout((int) config('asistente.timeout', 60))
                ->post(config('asistente.url'), [
                    'sesion' => $request->session()->getId(),
                    'mensaje' => $datos['mensaje'],
                    'rol' => $usuario->rol,
                    'usuario_id' => (int) $usuario->id,
                ]);
            if (!$respuesta->successful()) {
                return response()->json(['respuesta' => 'El asistente no está disponible en este momento. Intente de nuevo en un minuto.'], 503);
            }
            return response()->json(['respuesta' => (string) $respuesta->json('respuesta', 'No pude generar una respuesta.')]);
        } catch (ConnectionException $exception) {
            report($exception);
            return response()->json(['respuesta' => 'El asistente no está disponible en este momento. Intente de nuevo en un minuto.'], 503);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['respuesta' => 'El asistente no está disponible en este momento. Intente de nuevo en un minuto.'], 503);
        }
    }
}
