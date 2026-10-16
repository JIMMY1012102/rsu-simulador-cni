<?php

namespace App\Services;

use App\Models\Simulacro;
use App\Models\Usuario;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica del módulo de simulacros: consulta, creación y actualización
 * de la configuración junto con sus áreas y áreas de postulación.
 */
class SimulacroService
{
    private const CAMPOS_SIMULACRO = [
        'nombre',
        'tiempo_limite',
        'total_preguntas',
        'cantidad_alternativas',
        'puntaje_correcta',
        'puntaje_incorrecta',
        'puntaje_blanco',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    /**
     * Los estudiantes solo ven simulacros activos; admin y docente ven todos.
     */
    public function listar(Usuario $usuario): Collection
    {
        return Simulacro::query()
            ->with(['distribucion', 'areasPostulacion'])
            ->when(! $this->puedeGestionar($usuario), fn ($q) => $q->where('activo', true))
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn (Simulacro $s) => $this->formatear($s));
    }

    public function obtener(int $id, Usuario $usuario): ?array
    {
        $simulacro = Simulacro::query()
            ->with(['distribucion', 'areasPostulacion'])
            ->when(! $this->puedeGestionar($usuario), fn ($q) => $q->where('activo', true))
            ->find($id);

        return $simulacro ? $this->formatear($simulacro) : null;
    }

    public function crear(array $datos, Usuario $usuario): array
    {
        $simulacro = DB::transaction(function () use ($datos, $usuario) {
            $simulacro = Simulacro::create(
                Arr::only($datos, self::CAMPOS_SIMULACRO) + ['creado_por' => $usuario->id]
            );

            $this->guardarAsociaciones($simulacro, $datos);

            return $simulacro;
        });

        return $this->formatear($simulacro->fresh(['distribucion', 'areasPostulacion']));
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $simulacro = Simulacro::find($id);

        if (! $simulacro) {
            return null;
        }

        DB::transaction(function () use ($simulacro, $datos) {
            $simulacro->update(Arr::only($datos, self::CAMPOS_SIMULACRO));
            $this->guardarAsociaciones($simulacro, $datos);
        });

        return $this->formatear($simulacro->fresh(['distribucion', 'areasPostulacion']));
    }

    /**
     * Reemplaza la distribución por áreas y sincroniza las áreas de postulación,
     * para que un PUT repetido no duplique filas.
     */
    private function guardarAsociaciones(Simulacro $simulacro, array $datos): void
    {
        $simulacro->distribucion()->delete();
        $simulacro->distribucion()->createMany(
            collect($datos['areas'])->map(fn ($a) => [
                'area_id' => $a['area_id'],
                'nivel' => $a['nivel'] ?? 'mixto',
                'cantidad_preguntas' => $a['cantidad_preguntas'],
            ])->all()
        );

        if (array_key_exists('areas_postulacion', $datos)) {
            $simulacro->areasPostulacion()->sync($datos['areas_postulacion']);
        }
    }

    public function puedeGestionar(Usuario $usuario): bool
    {
        return in_array($usuario->rol, ['admin', 'docente'], true);
    }

    /**
     * Construye el JSON público del simulacro (ver contracts/simulacros.json).
     */
    private function formatear(Simulacro $simulacro): array
    {
        return [
            'id' => $simulacro->id,
            'nombre' => $simulacro->nombre,
            'tiempo_limite' => $simulacro->tiempo_limite,
            'total_preguntas' => $simulacro->total_preguntas,
            'cantidad_alternativas' => $simulacro->cantidad_alternativas,
            'puntaje_correcta' => (float) $simulacro->puntaje_correcta,
            'puntaje_incorrecta' => (float) $simulacro->puntaje_incorrecta,
            'puntaje_blanco' => (float) $simulacro->puntaje_blanco,
            'puntaje_maximo' => (float) $simulacro->puntaje_maximo,
            'fecha_inicio' => $simulacro->fecha_inicio?->format('Y-m-d H:i:s'),
            'fecha_fin' => $simulacro->fecha_fin?->format('Y-m-d H:i:s'),
            'activo' => $simulacro->activo,
            'areas' => $simulacro->distribucion->map(fn ($d) => [
                'area_id' => $d->area_id,
                'nivel' => $d->nivel,
                'cantidad_preguntas' => $d->cantidad_preguntas,
            ])->values()->all(),
            'areas_postulacion' => $simulacro->areasPostulacion->map(fn ($ap) => [
                'id' => $ap->id,
                'codigo' => $ap->codigo,
                'nombre' => $ap->nombre,
            ])->values()->all(),
        ];
    }
}
