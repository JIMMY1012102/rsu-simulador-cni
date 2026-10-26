<?php

namespace App\Services;

use App\Models\Simulacro;
use App\Models\Usuario;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        'carrera_id',
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
        $this->validarConfiguracion($datos);
        $this->validarCarreraYArea($datos);
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

        $this->validarConfiguracion($datos);
        $this->validarCarreraYArea($datos);
        DB::transaction(function () use ($simulacro, $datos, $id) {
            // El inicio también bloquea esta fila: no se cambia la configuración de exámenes ya usados.
            $simulacro->newQuery()->whereKey($simulacro->id)->lockForUpdate()->firstOrFail();
            if (DB::table('intentos')->where('simulacro_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'simulacro' => 'Este simulacro ya tiene intentos. Crea uno nuevo para cambiar su configuración.',
                ]);
            }
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

    /**
     * La distribución también se valida aquí: el servicio puede invocarse
     * sin un FormRequest (por ejemplo, desde otras partes de Laravel).
     * Nunca guardar un examen imposible de rendir.
     */
    private function validarConfiguracion(array $datos): void
    {
        $cuotas = $datos['areas'] ?? [];

        if (! is_array($cuotas) || $cuotas === [] || (int) ($datos['total_preguntas'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'areas' => 'Configura al menos una asignatura y un total válido de preguntas.',
            ]);
        }

        $suma = 0;
        $combinaciones = [];

        foreach ($cuotas as $cuota) {
            $areaId = (int) ($cuota['area_id'] ?? 0);
            $nivel = $cuota['nivel'] ?? 'mixto';
            $cantidad = (int) ($cuota['cantidad_preguntas'] ?? 0);
            $clave = $areaId.'|'.$nivel;

            if ($cantidad < 1 || ! in_array($nivel, ['basico', 'intermedio', 'avanzado', 'mixto'], true)
                || ! DB::table('areas')->where('id', $areaId)->where('activo', true)->exists()) {
                throw ValidationException::withMessages([
                    'areas' => 'La distribución contiene una asignatura inactiva o una cuota inválida.',
                ]);
            }

            if (isset($combinaciones[$clave])) {
                throw ValidationException::withMessages([
                    'areas' => 'No se puede repetir la misma asignatura y nivel.',
                ]);
            }

            $combinaciones[$clave] = true;
            $suma += $cantidad;
        }

        if ($suma !== (int) $datos['total_preguntas']) {
            throw ValidationException::withMessages([
                'areas' => 'La suma de cuotas debe coincidir con el total de preguntas.',
            ]);
        }

        foreach ($datos['areas_postulacion'] ?? [] as $areaId) {
            if (! DB::table('areas_postulacion')->where('id', $areaId)->where('activo', true)->exists()) {
                throw ValidationException::withMessages([
                    'areas_postulacion' => 'Todas las áreas de postulación deben estar activas.',
                ]);
            }
        }
    }

    /** Un perfil por carrera no puede apuntar a otra área ni quedar sin área asignada. */
    private function validarCarreraYArea(array $datos): void
    {
        $carreraId = $datos['carrera_id'] ?? null;
        if ($carreraId === null) {
            return;
        }
        $carrera = DB::table('carreras')->where('id', $carreraId)->where('activo', true)->first();
        if (! $carrera || ! in_array((int) $carrera->area_postulacion_id,
            array_map('intval', $datos['areas_postulacion'] ?? []), true)) {
            throw ValidationException::withMessages([
                'carrera_id' => 'La carrera del simulacro debe pertenecer a su área de postulación.',
            ]);
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
            'carrera_id' => $simulacro->carrera_id,
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
