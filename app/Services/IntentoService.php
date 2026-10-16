<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IntentoService
{
    public function iniciar(int $usuarioId, array $datos): object
    {
        return DB::transaction(function () use ($usuarioId, $datos) {
            $simulacro = null;

            if (!empty($datos['simulacro_id'])) {
                $simulacro = DB::table('simulacros')
                    ->where('id', $datos['simulacro_id'])
                    ->where('activo', 1)
                    ->first();

                if (!$simulacro) {
                    throw ValidationException::withMessages([
                        'simulacro_id' => 'El simulacro no existe o no esta activo.',
                    ]);
                }
            }

            if ($datos['tipo'] === 'simulacro' && !$simulacro) {
                throw ValidationException::withMessages([
                    'simulacro_id' => 'Debes indicar un simulacro activo.',
                ]);
            }

            $ahora = now();
            $totalPreguntas = $simulacro
                ? (int) $simulacro->total_preguntas
                : 0;

            $intentoId = DB::table('intentos')->insertGetId([
                'usuario_id' => $usuarioId,
                'carrera_id' => $datos['carrera_id'] ?? null,
                'simulacro_id' => $simulacro?->id,
                'tipo' => $datos['tipo'],
                'area_id' => $datos['area_id'] ?? null,
                'tema_id' => $datos['tema_id'] ?? null,
                'fecha_inicio' => $ahora,
                'fecha_limite' => $simulacro
                    ? $ahora->copy()->addMinutes((int) $simulacro->tiempo_limite)
                    : null,
                'estado' => 'en_curso',
                'total_preguntas' => $totalPreguntas,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            return DB::table('intentos')->where('id', $intentoId)->first();
        });
    }

    public function obtenerPropio(int $intentoId, int $usuarioId): object
    {
        $intento = DB::table('intentos')
            ->where('id', $intentoId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$intento) {
            abort(404, 'Intento no encontrado.');
        }

        return $intento;
    }

    public function guardarRespuesta(
        int $intentoId,
        int $usuarioId,
        array $datos
    ): object {
        return DB::transaction(function () use ($intentoId, $usuarioId, $datos) {
            $intento = DB::table('intentos')
                ->where('id', $intentoId)
                ->where('usuario_id', $usuarioId)
                ->lockForUpdate()
                ->first();

            if (!$intento) {
                abort(404, 'Intento no encontrado.');
            }

            if ($intento->estado !== 'en_curso') {
                throw ValidationException::withMessages([
                    'intento' => 'El intento ya no esta en curso.',
                ]);
            }

            if ($intento->fecha_limite && now()->greaterThanOrEqualTo($intento->fecha_limite)) {
                DB::table('intentos')->where('id', $intentoId)->update([
                    'estado' => 'enviado_auto',
                    'fecha_fin' => now(),
                    'updated_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'intento' => 'El tiempo del intento ha terminado.',
                ]);
            }

            $respuesta = DB::table('respuestas_estudiante')
                ->where('intento_id', $intentoId)
                ->where('pregunta_id', $datos['pregunta_id'])
                ->first();

            if (!$respuesta) {
                throw ValidationException::withMessages([
                    'pregunta_id' => 'La pregunta no pertenece a este intento.',
                ]);
            }

            if (!empty($datos['alternativa_id'])) {
                $alternativaValida = DB::table('alternativas')
                    ->where('id', $datos['alternativa_id'])
                    ->where('pregunta_id', $datos['pregunta_id'])
                    ->exists();

                if (!$alternativaValida) {
                    throw ValidationException::withMessages([
                        'alternativa_id' => 'La alternativa no pertenece a la pregunta.',
                    ]);
                }
            }

            DB::table('respuestas_estudiante')
                ->where('id', $respuesta->id)
                ->update([
                    'alternativa_id' => $datos['alternativa_id'] ?? null,
                    'respondida_en' => now(),
                    'updated_at' => now(),
                ]);

            return DB::table('respuestas_estudiante')
                ->where('id', $respuesta->id)
                ->select([
                    'id',
                    'intento_id',
                    'pregunta_id',
                    'orden',
                    'alternativa_id',
                    'respondida_en',
                ])
                ->first();
        });
    }

    public function finalizar(int $intentoId, int $usuarioId): object
    {
        $intento = DB::table('intentos')
            ->where('id', $intentoId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$intento) {
            abort(404, 'Intento no encontrado.');
        }

        if ($intento->estado !== 'en_curso') {
            throw ValidationException::withMessages([
                'intento' => 'El intento ya fue finalizado.',
            ]);
        }

        DB::table('intentos')->where('id', $intentoId)->update([
            'estado' => $intento->fecha_limite
                && now()->greaterThanOrEqualTo($intento->fecha_limite)
                    ? 'enviado_auto'
                    : 'finalizado',
            'fecha_fin' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('intentos')->where('id', $intentoId)->first();
    }
}
