<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Carga resultados sintéticos para presentar el análisis del asistente docente.
 * No forma parte de DatabaseSeeder; se ejecuta manualmente y con protección de destino.
 */
class DocenteResultadosDemoSeeder extends Seeder
{
    private const SIMULACRO = 'DEMO FICTICIO - Muestra de resultados docentes';

    private const AREAS = [
        'Habilidad Matemática',
        'Razonamiento Verbal',
        'Inglés',
        'Lenguaje',
    ];

    /** Aciertos de cinco preguntas por área; datos completamente ilustrativos. */
    private const PERFILES = [
        [1, 4, 3, 3],
        [2, 3, 4, 4],
        [2, 2, 5, 3],
        [3, 4, 4, 5],
    ];

    public function run(): void
    {
        $this->validarDestino();

        DB::transaction(function (): void {
            if (DB::table('simulacros')->where('nombre', self::SIMULACRO)->exists()) {
                $this->command?->warn('El simulacro ficticio ya existe; no se duplicó.');

                return;
            }

            $docente = DB::table('usuarios')
                ->where('usuario', 'docente_demo')
                ->where('rol', 'docente')
                ->where('activo', true)
                ->first();

            if (! $docente || ! $docente->seccion_id) {
                throw new RuntimeException(
                    'Se requiere la cuenta docente_demo activa y asignada a una sección demo.'
                );
            }

            if (! DB::table('secciones')
                ->where('id', $docente->seccion_id)
                ->where('activo', true)
                ->exists()) {
                throw new RuntimeException('La sección demo del docente no existe o está inactiva.');
            }

            $areas = DB::table('areas')
                ->whereIn('nombre', self::AREAS)
                ->where('activo', true)
                ->pluck('id', 'nombre');

            if ($areas->count() !== count(self::AREAS)) {
                throw new RuntimeException('Falta alguna de las cuatro áreas requeridas para la demo.');
            }

            $now = now();
            $simulacroId = DB::table('simulacros')->insertGetId([
                'nombre' => self::SIMULACRO,
                'tiempo_limite' => 30,
                'total_preguntas' => 20,
                'cantidad_alternativas' => 4,
                'fecha_inicio' => $now->copy()->subDay(),
                'fecha_fin' => $now->copy()->addDays(30),
                'activo' => true,
                'creado_por' => $docente->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (self::PERFILES as $index => $aciertosPorArea) {
                $username = $index === 0
                    ? 'estudiante_demo'
                    : sprintf('demo_davis_alumno_%02d', $index + 1);
                $estudiante = DB::table('usuarios')->where('usuario', $username)->first();

                if ($estudiante) {
                    if ($estudiante->rol !== 'estudiante'
                        || ! $estudiante->activo
                        || (int) $estudiante->seccion_id !== (int) $docente->seccion_id) {
                        throw new RuntimeException("La cuenta {$username} no pertenece a la sección demo; no se modificó.");
                    }
                    $estudianteId = $estudiante->id;
                } else {
                    if ($index === 0) {
                        throw new RuntimeException('Falta estudiante_demo. Ejecuta primero el seeder base local de demostración.');
                    }

                    $estudianteId = DB::table('usuarios')->insertGetId([
                        'nombres' => 'Alumno demo',
                        'apellidos' => sprintf('Ficticio %02d', $index + 1),
                        'usuario' => $username,
                        'password' => Hash::make(Str::random(48)),
                        'rol' => 'estudiante',
                        'seccion_id' => $docente->seccion_id,
                        'carrera_id' => null,
                        'activo' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $puntajes = array_map(
                    fn (int $aciertos): float => round(($aciertos * 20) - ((5 - $aciertos) * 1.125), 3),
                    $aciertosPorArea
                );
                $totalAciertos = array_sum($aciertosPorArea);

                $intentoId = DB::table('intentos')->insertGetId([
                    'usuario_id' => $estudianteId,
                    'carrera_id' => null,
                    'simulacro_id' => $simulacroId,
                    'tipo' => 'simulacro',
                    'area_id' => null,
                    'tema_id' => null,
                    'fecha_inicio' => $now->copy()->subHours(2),
                    'fecha_limite' => $now->copy()->subHour(),
                    'fecha_fin' => $now->copy()->subHour(),
                    'estado' => 'finalizado',
                    'total_preguntas' => 20,
                    'puntaje_total' => array_sum($puntajes),
                    'correctas' => $totalAciertos,
                    'incorrectas' => 20 - $totalAciertos,
                    'en_blanco' => 0,
                    'doble_marca' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (self::AREAS as $areaIndex => $nombreArea) {
                    $aciertos = $aciertosPorArea[$areaIndex];
                    DB::table('resultados_area')->insert([
                        'intento_id' => $intentoId,
                        'area_id' => $areas[$nombreArea],
                        'total_preguntas' => 5,
                        'aciertos' => $aciertos,
                        'incorrectas' => 5 - $aciertos,
                        'en_blanco' => 0,
                        'puntaje' => $puntajes[$areaIndex],
                        'porcentaje' => $aciertos * 20,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $this->command?->info('Creados 4 estudiantes y resultados DEMO FICTICIOS en la sección demo.');
        });
    }

    private function validarDestino(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = config("database.connections.{$connection}.host");

        if ($connection !== 'mysql' || blank($database)) {
            throw new RuntimeException('La carga demo requiere una base MySQL/MariaDB identificada.');
        }

        if (app()->environment(['local', 'testing'])) {
            if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
                throw new RuntimeException('La demo local solo se permite en una base de datos local.');
            }

            return;
        }

        $permitida = (bool) config('cni.docente_demo_seed_enabled');
        $baseEsperada = config('cni.docente_demo_seed_database');

        if (! $permitida || ! is_string($baseEsperada) || $baseEsperada === '' || $database !== $baseEsperada) {
            throw new RuntimeException(
                'En nube, habilita explícitamente CNI_DOCENTE_DEMO_SEED_ENABLED y confirma el nombre exacto de la base demo.'
            );
        }
    }
}