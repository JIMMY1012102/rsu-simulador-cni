<?php

namespace Database\Seeders;

use App\Services\ResultadoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Datos ficticios de demostración: 10 estudiantes por sección que rindieron
 * el simulacro con ID 5. Solo se permite en bases locales/de pruebas.
 *
 * Acceso de las cuentas: demo_sim5_5a_01 ... demo_sim5_5d_10
 * Contraseña inicial: DemoCNI2026!
 */
class AlumnosSimulacroCincoDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los resultados ficticios solo se permiten en local/testing.');
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Este seeder requiere MariaDB/MySQL.');
        }

        $host = config('database.connections.'.config('database.default').'.host');
        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('No se permite insertar datos demo en una base remota.');
        }

        foreach (['usuarios', 'secciones', 'simulacros', 'simulacro_area', 'intentos',
            'respuestas_estudiante', 'resultados_area', 'preguntas', 'alternativas', 'temas', 'areas'] as $tabla) {
            if (! Schema::hasTable($tabla)) {
                throw new RuntimeException("Falta la tabla {$tabla}. Ejecuta primero las migraciones.");
            }
        }

        $simulacro = DB::table('simulacros')->where('id', 5)->first();
        if (! $simulacro) {
            throw new RuntimeException('No existe el simulacro con ID 5. No se creó ningún alumno ni intento.');
        }

        $cuotas = DB::table('simulacro_area')
            ->where('simulacro_id', 5)
            ->orderByRaw("CASE WHEN nivel = 'mixto' THEN 1 ELSE 0 END")
            ->orderBy('id')
            ->get();

        if ($cuotas->isEmpty() || (int) $cuotas->sum('cantidad_preguntas') !== (int) $simulacro->total_preguntas) {
            throw new RuntimeException('La distribución de preguntas del simulacro 5 no coincide con su total.');
        }

        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $seccion = DB::table('secciones')
                ->where('grado', '5to')
                ->where('nombre', $letra)
                ->where('activo', true)
                ->first();

            if (! $seccion) {
                throw new RuntimeException("No existe la sección activa 5to {$letra}. Ejecuta SeccionSeeder primero.");
            }

            $secciones[$letra] = $seccion;
        }

        $resultadoService = app(ResultadoService::class);

        DB::transaction(function () use ($simulacro, $cuotas, $secciones, $resultadoService) {
            $indiceGlobal = 0;

            foreach ($secciones as $letra => $seccion) {
                for ($numero = 1; $numero <= 10; $numero++) {
                    $indiceGlobal++;
                    $sufijo = str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
                    $usuario = 'demo_sim5_5'.strtolower($letra).'_'.$sufijo;

                    $existente = DB::table('usuarios')->where('usuario', $usuario)->first();

                    if ($existente && ($existente->rol !== 'estudiante'
                        || (int) $existente->seccion_id !== (int) $seccion->id)) {
                        throw new RuntimeException("La cuenta {$usuario} ya existe con otra función o sección. No se modificó.");
                    }

                    if (! $existente) {
                        $datosUsuario = [
                            'nombres' => 'Alumno '.$sufijo,
                            'apellidos' => 'Demostracion 5'.$letra,
                            'usuario' => $usuario,
                            'password' => Hash::make('DemoCNI2026!'),
                            'rol' => 'estudiante',
                            'seccion_id' => $seccion->id,
                            'carrera_id' => null,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if (Schema::hasColumn('usuarios', 'area_postulacion_id')) {
                            $datosUsuario['area_postulacion_id'] = null;
                        }

                        DB::table('usuarios')->insert($datosUsuario);
                        $usuarioId = (int) DB::table('usuarios')->where('usuario', $usuario)->value('id');
                    } else {
                        $usuarioId = (int) $existente->id;
                    }

                    $yaRendido = DB::table('intentos')
                        ->where('usuario_id', $usuarioId)
                        ->where('simulacro_id', 5)
                        ->exists();

                    if ($yaRendido) {
                        $this->command?->info("Ya tiene intento del simulacro 5: {$usuario}");
                        continue;
                    }

                    $preguntasSeleccionadas = collect();

                    foreach ($cuotas as $cuota) {
                        $consulta = DB::table('preguntas as p')
                            ->join('temas as t', 't.id', '=', 'p.tema_id')
                            ->join('areas as a', 'a.id', '=', 't.area_id')
                            ->where('a.id', $cuota->area_id)
                            ->where('p.activo', true)
                            ->where('t.activo', true)
                            ->where('a.activo', true)
                            ->when($cuota->nivel !== 'mixto', fn ($q) => $q->where('p.nivel', $cuota->nivel))
                            ->whereRaw('(SELECT COUNT(*) FROM alternativas WHERE pregunta_id = p.id) = ?', [(int) $simulacro->cantidad_alternativas])
                            ->whereRaw('(SELECT COUNT(*) FROM alternativas WHERE pregunta_id = p.id AND es_correcta = 1) = 1');

                        if ($preguntasSeleccionadas->isNotEmpty()) {
                            $consulta->whereNotIn('p.id', $preguntasSeleccionadas->pluck('id')->all());
                        }

                        $seleccion = $consulta->inRandomOrder()
                            ->limit((int) $cuota->cantidad_preguntas)
                            ->get(['p.id', 'a.id as area_id']);

                        if ($seleccion->count() !== (int) $cuota->cantidad_preguntas) {
                            throw new RuntimeException(
                                'El banco no tiene suficientes preguntas válidas para completar el simulacro 5. No se completó la carga.'
                            );
                        }

                        $preguntasSeleccionadas = $preguntasSeleccionadas->concat($seleccion);
                    }

                    $inicio = now()->subDays(2)->subMinutes($indiceGlobal * 7);
                    $duracion = max(1, min((int) $simulacro->tiempo_limite - 1, (int) round($simulacro->tiempo_limite * 0.82)));
                    $fin = $inicio->copy()->addMinutes($duracion);

                    $intentoId = DB::table('intentos')->insertGetId([
                        'usuario_id' => $usuarioId,
                        'carrera_id' => null,
                        'simulacro_id' => 5,
                        'tipo' => 'simulacro',
                        'area_id' => null,
                        'tema_id' => null,
                        'fecha_inicio' => $inicio,
                        'fecha_limite' => $inicio->copy()->addMinutes((int) $simulacro->tiempo_limite),
                        'fecha_fin' => $fin,
                        'estado' => 'finalizado',
                        'total_preguntas' => $preguntasSeleccionadas->count(),
                        'created_at' => $inicio,
                        'updated_at' => $fin,
                    ]);

                    foreach ($preguntasSeleccionadas->values() as $orden => $pregunta) {
                        $alternativas = DB::table('alternativas')
                            ->where('pregunta_id', $pregunta->id)
                            ->orderBy('letra')
                            ->get(['id', 'es_correcta']);

                        $correcta = $alternativas->firstWhere('es_correcta', true);
                        $incorrectas = $alternativas->where('es_correcta', false)->values();

                        if (! $correcta || $incorrectas->isEmpty()) {
                            throw new RuntimeException("La pregunta {$pregunta->id} no tiene alternativas válidas.");
                        }

                        $porcentajeAcierto = 55 + (($indiceGlobal * 9) % 36);
                        $patron = (($orden + 1) * 37 + $indiceGlobal * 17) % 100;

                        if ($patron < $porcentajeAcierto) {
                            $alternativaId = $correcta->id;
                        } elseif ($patron < $porcentajeAcierto + 20) {
                            $alternativaId = $incorrectas->first()->id;
                        } else {
                            $alternativaId = null;
                        }

                        DB::table('respuestas_estudiante')->insert([
                            'intento_id' => $intentoId,
                            'pregunta_id' => $pregunta->id,
                            'orden' => $orden + 1,
                            'alternativa_id' => $alternativaId,
                            'estado_respuesta' => null,
                            'puntaje_obtenido' => null,
                            'respondida_en' => $alternativaId ? $fin : null,
                            'created_at' => $inicio,
                            'updated_at' => $fin,
                        ]);
                    }

                    $resultadoService->calificar((int) $intentoId);
                    $this->command?->info("Alumno e intento del simulacro 5 creados: {$usuario} (5to {$letra}).");
                }
            }
        });

        $this->command?->info('Carga demo finalizada: hasta 40 alumnos, distribuidos entre 5to A, B, C y D.');
        $this->command?->warn('Cuentas ficticias de demostración. Usuario: demo_sim5_5[sección]_[01-10]. Contraseña: DemoCNI2026!');
    }
}
