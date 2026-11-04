<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Demostración de 5 perfiles académicos, nunca datos oficiales.
 * Ejecutar solo en desarrollo o pruebas, después de definir el catálogo A-E.
 * No forma parte de DatabaseSeeder.
 */
class SimulacrosCincoAreasDemoSeeder extends Seeder
{
    public const PREFIJO = 'DEMO CNI - Área ';

    // Cuotas: Verbal(10), R. Matemático(10), Matemática(6), Ciencias(8), Humanidades(6).
    // Todos suman 20 preguntas distintas dentro del simulacro. Son proporciones ILUSTRATIVAS.
    private const PERFILES = [
        'A' => ['Razonamiento Verbal' => 3, 'Razonamiento Matemático' => 3, 'Matemática' => 4, 'Ciencias' => 8, 'Humanidades' => 2],
        'B' => ['Razonamiento Verbal' => 2, 'Razonamiento Matemático' => 5, 'Matemática' => 6, 'Ciencias' => 6, 'Humanidades' => 1],
        'C' => ['Razonamiento Verbal' => 2, 'Razonamiento Matemático' => 6, 'Matemática' => 6, 'Ciencias' => 5, 'Humanidades' => 1],
        'D' => ['Razonamiento Verbal' => 6, 'Razonamiento Matemático' => 5, 'Matemática' => 4, 'Ciencias' => 2, 'Humanidades' => 3],
        'E' => ['Razonamiento Verbal' => 9, 'Razonamiento Matemático' => 2, 'Matemática' => 1, 'Ciencias' => 2, 'Humanidades' => 6],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los simulacros de demostración están prohibidos fuera de local/testing.');
        }

        // La fuente de preguntas de prueba se mantiene separada de los seeders de producción.
        $this->call(ExamenDemoSeeder::class);

        DB::transaction(function () {
            $areas = DB::table('areas_postulacion')->where('activo', true)->pluck('id', 'codigo');
            $asignaturas = DB::table('areas')->where('activo', true)->pluck('id', 'nombre');

            foreach (self::PERFILES as $codigo => $distribucion) {
                if (! isset($areas[$codigo])) {
                    throw new RuntimeException("Falta el área de postulación {$codigo} del catálogo A-E.");
                }
                if (array_sum($distribucion) !== 20) {
                    throw new RuntimeException("La suma de cuotas del área {$codigo} debe ser 20.");
                }

                $nombre = self::PREFIJO.$codigo;
                $existente = DB::table('simulacros')->where('nombre', $nombre)->first();
                if ($existente) {
                    // Nunca rehacer un simulacro con intentos: conservar respuestas e historial.
                    if (DB::table('intentos')->where('simulacro_id', $existente->id)->exists()) {
                        throw new RuntimeException("El simulacro {$nombre} tiene intentos: no se puede reconstruir.");
                    }
                    // Idempotencia sin modificar configuraciones existentes.
                    continue;
                }

                $simulacroId = DB::table('simulacros')->insertGetId([
                    'nombre' => $nombre,
                    'tiempo_limite' => 45,
                    'total_preguntas' => 20,
                    'cantidad_alternativas' => 4,
                    'fecha_inicio' => now()->subDay(),
                    'fecha_fin' => now()->addDays(30),
                    'activo' => true,
                    'creado_por' => DB::table('usuarios')->where('rol', 'admin')->value('id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('area_postulacion_simulacro')->insert([
                    'simulacro_id' => $simulacroId,
                    'area_postulacion_id' => $areas[$codigo],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($distribucion as $asignatura => $cantidad) {
                    if (! isset($asignaturas[$asignatura])) {
                        throw new RuntimeException("Faltan preguntas de prueba en {$asignatura}.");
                    }
                    $disponibles = DB::table('preguntas as p')
                        ->join('temas as t', 't.id', '=', 'p.tema_id')
                        ->where('t.area_id', $asignaturas[$asignatura])->where('p.activo', true)
                        ->count();
                    if ($disponibles < $cantidad) {
                        throw new RuntimeException("Banco insuficiente: {$asignatura} requiere {$cantidad}.");
                    }

                    DB::table('simulacro_area')->insert([
                        'simulacro_id' => $simulacroId,
                        'area_id' => $asignaturas[$asignatura],
                        'nivel' => 'mixto',
                        'cantidad_preguntas' => $cantidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
