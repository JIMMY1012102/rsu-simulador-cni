<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Services\Alumno\ExamenIndependienteService;
use App\Services\IntentoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** 20 alumnos ficticios y 2 simulacros calificados por alumno. Solo local/testing. */
class DemoAlumnosSimulacrosSeeder extends Seeder
{
    private const PASSWORD = 'DemoCNI2026!';
    private const INTENTOS_POR_ALUMNO = 2;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los datos demo solo se permiten en local/testing.');
        }

        $nombres = ['Alex','Camila','Diego','Valeria','Mateo','Luciana','Sebastián','Daniela','Thiago','Mariana','Gabriel','Fernanda','Adrián','Sofía','Nicolás','Alessia','Joaquín','Ximena','Bruno','Mía'];
        $apellidos = ['Demo Rojas','Demo Flores','Demo Torres','Demo Castro','Demo Vega','Demo Ramos','Demo Salazar','Demo Paredes','Demo Medina','Demo León','Demo Cruz','Demo Vargas','Demo Silva','Demo Herrera','Demo Campos','Demo Navarro','Demo Ruiz','Demo Mendoza','Demo Castillo','Demo Reyes'];
        $codigoAreaDemo = 'C'; // Área C ya está disponible en el banco local del simulador.
        $areas = DB::table('areas_postulacion')->where('activo', true)->get()->keyBy('codigo');
        $secciones = DB::table('secciones')->where('grado', '5to')->where('activo', true)->get()->keyBy('nombre');

        foreach (['A','B','C','D'] as $letra) {
            if (! $secciones->has($letra)) {
                throw new RuntimeException("No existe la sección 5to {$letra}; ejecuta SeccionSeeder.");
            }
        }
        if (! $areas->has($codigoAreaDemo) || ! DB::table('carreras')->where('area_postulacion_id', $areas[$codigoAreaDemo]->id)->where('activo', true)->exists()) {
            throw new RuntimeException('Falta el área C o una carrera activa asociada.');
        }

        $tieneArea = Schema::hasColumn('usuarios', 'area_postulacion_id');
        $alumnos = [];
        foreach (['A','B','C','D'] as $i => $letra) {
            for ($n = 1; $n <= 5; $n++) {
                $idx = $i * 5 + $n - 1;
                $codigo = $codigoAreaDemo;
                $areaId = (int) $areas[$codigo]->id;
                $carreraId = (int) DB::table('carreras')->where('area_postulacion_id', $areaId)->where('activo', true)->orderBy('id')->value('id');
                $login = 'demo_' . strtolower($letra) . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
                $datos = [
                    'nombres' => $nombres[$idx], 'apellidos' => $apellidos[$idx],
                    'password' => Hash::make(self::PASSWORD), 'rol' => 'estudiante',
                    'seccion_id' => (int) $secciones[$letra]->id, 'carrera_id' => $carreraId,
                    'activo' => true, 'created_at' => now(), 'updated_at' => now(),
                ];
                if ($tieneArea) $datos['area_postulacion_id'] = $areaId;
                DB::table('usuarios')->updateOrInsert(['usuario' => $login], $datos);
                $alumnos[] = Usuario::query()->where('usuario', $login)->firstOrFail();
            }
        }

        $examenes = app(ExamenIndependienteService::class);
        $motor = app(IntentoService::class);
        foreach ($alumnos as $alumno) {
            $areaId = $alumno->area_postulacion_id ?? $alumno->carrera?->area_postulacion_id;
            $codigo = DB::table('areas_postulacion')->where('id', $areaId)->value('codigo');
            $simulacros = DB::table('simulacros')->where('nombre', 'like', 'Examen independiente CNI · Área '.$codigo.' ·%')->pluck('id');
            $hechos = $simulacros->isEmpty() ? 0 : DB::table('intentos')->where('usuario_id', $alumno->id)->where('tipo', 'simulacro')->whereIn('simulacro_id', $simulacros)->count();

            for ($i = $hechos; $i < self::INTENTOS_POR_ALUMNO; $i++) {
                // Usa el sorteo oficial del examen y el servicio real de calificación.
                $intento = $examenes->iniciar($alumno);
                $respuestas = DB::table('respuestas_estudiante')->where('intento_id', $intento->id)->orderBy('orden')->get(['id','pregunta_id']);
                if ($respuestas->isEmpty() || $respuestas->count() !== (int) $intento->total_preguntas) {
                    throw new RuntimeException("El intento {$intento->id} no contiene todas sus preguntas.");
                }
                $opciones = DB::table('alternativas')->whereIn('pregunta_id', $respuestas->pluck('pregunta_id'))->get(['id','pregunta_id','es_correcta'])->groupBy('pregunta_id');
                $pctCorrectas = random_int(38, 82);
                $pctIncorrectas = random_int(5, max(5, min(28, 92 - $pctCorrectas)));
                $ahora = now();
                $cambios = [];

                foreach ($respuestas as $respuesta) {
                    $lista = $opciones->get($respuesta->pregunta_id, collect());
                    $correcta = $lista->firstWhere('es_correcta', 1);
                    $incorrectas = $lista->where('es_correcta', 0)->values();
                    $alternativaId = null;
                    if ($correcta && $incorrectas->isNotEmpty()) {
                        $azar = random_int(1, 100);
                        if ($azar <= $pctCorrectas) $alternativaId = (int) $correcta->id;
                        elseif ($azar <= $pctCorrectas + $pctIncorrectas) $alternativaId = (int) $incorrectas->random()->id;
                    }
                    $cambios[] = [
                        'id' => (int) $respuesta->id, 'alternativa_id' => $alternativaId,
                        'respondida_en' => $alternativaId === null ? null : $ahora,
                        'created_at' => $ahora, 'updated_at' => $ahora,
                    ];
                }
                DB::table('respuestas_estudiante')->upsert($cambios, ['id'], ['alternativa_id','respondida_en','updated_at']);
                $motor->finalizar((int) $intento->id, (int) $alumno->id);

                // Antigüedad y duración variables para poblar historiales y reportes.
                $inicio = Carbon::now()->subDays(random_int(1, 14))->setTime(random_int(8, 17), random_int(0, 59), random_int(0, 59));
                $duracion = random_int(38, 60);
                $fin = $inicio->copy()->addMinutes($duracion);
                DB::table('intentos')->where('id', $intento->id)->update([
                    'fecha_inicio' => $inicio, 'fecha_limite' => $inicio->copy()->addMinutes(60),
                    'fecha_fin' => $fin, 'created_at' => $inicio, 'updated_at' => $fin,
                ]);
                $marcas = [];
                foreach (DB::table('respuestas_estudiante')->where('intento_id', $intento->id)->get(['id','alternativa_id']) as $fila) {
                    $marcas[] = [
                        'id' => (int) $fila->id, 'respondida_en' => $fila->alternativa_id === null ? null : $inicio->copy()->addSeconds(random_int(1, $duracion * 60)),
                        'created_at' => $inicio, 'updated_at' => $fin,
                    ];
                }
                DB::table('respuestas_estudiante')->upsert($marcas, ['id'], ['respondida_en','created_at','updated_at']);
            }
        }

        $this->command?->info('Listo: 20 alumnos (5 por sección) y hasta 40 simulacros calificados.');
        $this->command?->line('Contraseña común: '.self::PASSWORD);
        $this->command?->line('Usuarios: demo_a01–demo_a05, demo_b01–demo_b05, demo_c01–demo_c05 y demo_d01–demo_d05.');
    }
}
