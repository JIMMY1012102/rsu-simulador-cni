<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreguntaService
{
    public function listar()
    {
        return DB::table('preguntas')
            ->where('activo', true)
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function obtener(int $id)
    {
        return DB::table('preguntas')
            ->where('activo', true)
            ->where('id', $id)
            ->first();
    }

    public function guardar(array $datos, ?int $usuarioId = null): int
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $this->validarTema($datos['tema_id']);

            $preguntaId = DB::table('preguntas')->insertGetId([
                'tema_id' => $datos['tema_id'],
                'enunciado' => $datos['enunciado'],
                'imagen' => $datos['imagen'] ?? null,
                'nivel' => $datos['nivel'],
                'solucion' => $datos['solucion'],
                'creado_por' => $usuarioId,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->guardarAlternativas(
                $preguntaId,
                $datos['alternativas']
            );

            return $preguntaId;
        });
    }

    public function actualizar(int $id, array $datos): bool
    {
        return DB::transaction(function () use ($id, $datos) {
            $existe = DB::table('preguntas')
                ->where('id', $id)
                ->where('activo', true)
                ->lockForUpdate()
                ->exists();

            if (!$existe) {
                return false;
            }

            $this->validarTema($datos['tema_id']);

            DB::table('preguntas')
                ->where('id', $id)
                ->update([
                    'tema_id' => $datos['tema_id'],
                    'enunciado' => $datos['enunciado'],
                    'imagen' => $datos['imagen'] ?? null,
                    'nivel' => $datos['nivel'],
                    'solucion' => $datos['solucion'],
                    'updated_at' => now(),
                ]);

            DB::table('alternativas')
                ->where('pregunta_id', $id)
                ->delete();

            $this->guardarAlternativas(
                $id,
                $datos['alternativas']
            );

            return true;
        });
    }

    public function eliminar(int $id): bool
    {
        return DB::table('preguntas')
            ->where('id', $id)
            ->where('activo', true)
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]) > 0;
    }

    private function guardarAlternativas(
        int $preguntaId,
        array $alternativas
    ): void {
        foreach ($alternativas as $alternativa) {
            DB::table('alternativas')->insert([
                'pregunta_id' => $preguntaId,
                'letra' => $alternativa['letra'],
                'texto' => $alternativa['texto'],
                'es_correcta' => $alternativa['es_correcta'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function validarTema(int $temaId): void
    {
        $existe = DB::table('temas')
            ->join('areas', 'temas.area_id', '=', 'areas.id')
            ->where('temas.id', $temaId)
            ->where('temas.activo', true)
            ->where('areas.activo', true)
            ->exists();

        if (!$existe) {
            throw ValidationException::withMessages([
                'tema_id' => 'El tema o su área no están activos.',
            ]);
        }
    }
}
