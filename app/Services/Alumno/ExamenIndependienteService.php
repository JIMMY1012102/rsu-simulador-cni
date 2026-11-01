<?php

namespace App\Services\Alumno;

use App\Models\Usuario;
use App\Services\IntentoService;
use App\Support\PerfilExamenCni;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Configura una plantilla por área y reutiliza el motor de intentos y calificación. */
class ExamenIndependienteService
{
    public function __construct(private readonly IntentoService $intentos) {}

    public function iniciar(Usuario $usuario): object
    {
        return DB::transaction(function () use ($usuario) {
            // Serializa doble clic y peticiones simultáneas del mismo alumno.
            $perfil = Usuario::query()->whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            abort_unless($perfil->activo && $perfil->rol === 'estudiante', 403);

            $carrera = $perfil->carrera;
            $areaId = $perfil->area_postulacion_id ?? $carrera?->area_postulacion_id;
            abort_if($carrera && (! $carrera->activo
                || (int) $carrera->area_postulacion_id !== (int) $areaId), 403);
            // El bloqueo del área evita crear dos plantillas al entrar dos alumnos a la vez.
            $area = DB::table('areas_postulacion')->where('id', $areaId)
                ->where('activo', true)->lockForUpdate()->first();
            abort_unless($area && isset(PerfilExamenCni::DISTRIBUCION[$area->codigo]), 403);

            $cuotas = PerfilExamenCni::DISTRIBUCION[$area->codigo];
            // Una configuración nueva obtiene otra plantilla; no altera exámenes anteriores.
            $version = substr(hash('sha256', json_encode([$cuotas, PerfilExamenCni::ORDEN, 180, 5])), 0, 10);
            $nombre = 'Examen independiente CNI · Área '.$area->codigo.' · '.$version;
            $simulacro = DB::table('simulacros')->where('nombre', $nombre)->first();
            if ($simulacro) {
                $abierto = $this->intentos->enCurso($perfil->id, $simulacro->id);
                if ($abierto) {
                    return $abierto;
                }
            } else {
                $asignaturas = DB::table('areas')->where('activo', true)->pluck('id', 'nombre');
                foreach ($cuotas as $prefijo => $cantidad) {
                    $curso = PerfilExamenCni::CURSOS[$prefijo][0];
                    if (! isset($asignaturas[$curso])) {
                        throw ValidationException::withMessages([
                            'preguntas' => 'El banco del área '.$area->codigo.' aún no está completo: falta '.$curso.'.',
                        ]);
                    }
                }

                $ahora = now();
                $simulacroId = DB::table('simulacros')->insertGetId([
                    'nombre' => $nombre, 'tiempo_limite' => 180,
                    'total_preguntas' => array_sum($cuotas), 'cantidad_alternativas' => 5,
                    'puntaje_correcta' => 20, 'puntaje_incorrecta' => -1.125, 'puntaje_blanco' => 0,
                    // Disponibilidad permanente; cada intento conserva su propio límite de 180 minutos.
                    'fecha_inicio' => '2000-01-01 00:00:00', 'fecha_fin' => '2099-12-31 23:59:59',
                    'activo' => true, 'carrera_id' => null, 'creado_por' => null,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
                DB::table('area_postulacion_simulacro')->insert([
                    'simulacro_id' => $simulacroId, 'area_postulacion_id' => $area->id,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
                foreach (PerfilExamenCni::ORDEN as $prefijo) {
                    if (! isset($cuotas[$prefijo])) {
                        continue;
                    }
                    DB::table('simulacro_area')->insert([
                        'simulacro_id' => $simulacroId,
                        'area_id' => $asignaturas[PerfilExamenCni::CURSOS[$prefijo][0]],
                        'nivel' => 'mixto', 'cantidad_preguntas' => $cuotas[$prefijo],
                        'created_at' => $ahora, 'updated_at' => $ahora,
                    ]);
                }
                $simulacro = (object) ['id' => $simulacroId];
            }

            return $this->intentos->iniciar($perfil->id, [
                'tipo' => 'simulacro', 'simulacro_id' => $simulacro->id,
                'orden_por_curso' => true,
            ]);
        }, 3);
    }
}
