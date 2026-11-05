<?php

/**
 * Preparación/verificación aislada de la prueba real en Chromium.
 * NO USAR fuera del runner temporal de GitHub Actions.
 */
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (getenv('CNI_E2E_CI_ONLY') !== '1'
    || config('app.env') !== 'testing'
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== 'cni_browser_ci'
    || config('database.connections.mysql.host') !== '127.0.0.1') {
    fwrite(STDERR, "ABORTADO: solo funciona en MariaDB efímera cni_browser_ci de CI.\n");
    exit(2);
}

function comprobar(bool $condicion, string $mensaje): void
{
    if (! $condicion) {
        fwrite(STDERR, "[ERROR] ".$mensaje."\n");
        exit(1);
    }
    echo "[OK] ".$mensaje."\n";
}

$accion = $argv[1] ?? '';
$esperadas = [
    'A' => 'Ciencias de la Salud',
    'B' => 'Ciencias Básicas',
    'C' => 'Ingenierías',
    'D' => 'Ciencias Económicas y de la Gestión',
    'E' => 'Humanidades y Ciencias Jurídicas y Sociales',
];
$actuales = DB::table('areas_postulacion')->orderBy('codigo')->pluck('nombre', 'codigo')->all();
comprobar($actuales === $esperadas, 'Catálogo A-E completo y ordenado');

if ($accion === 'prepare') {
    $clave = getenv('CNI_E2E_PASSWORD');
    comprobar(is_string($clave) && strlen($clave) >= 24, 'Existe una contraseña DEMO aleatoria para CI');
    $nombres = ['admin_demo', 'estudiante_demo', 'docente_demo'];
    comprobar(DB::table('usuarios')->whereIn('usuario', $nombres)->count() === 3, 'Existen las tres cuentas DEMO');

    $hash = Hash::make($clave);
    DB::table('usuarios')->whereIn('usuario', $nombres)
        ->update(['password' => $hash, 'activo' => true]);
    DB::table('usuarios')->where('usuario', 'estudiante_demo')
        ->update(['area_postulacion_id' => null, 'carrera_id' => null]);
    comprobar(
        DB::table('simulacros')->where('nombre', 'DEMO CNI - Área C')->exists(),
        'Simulacro DEMO de Ingeniería disponible'
    );
    echo "[OK] Credenciales de CI preparadas; no se imprimen.\n";
    exit(0);
}

if ($accion !== 'verify') {
    fwrite(STDERR, "Uso: php tests/e2e/fixture.php prepare|verify\n");
    exit(2);
}

$estudiante = DB::table('usuarios')->where('usuario', 'estudiante_demo')->first();
$docente = DB::table('usuarios')->where('usuario', 'docente_demo')->first();
comprobar($estudiante !== null && $docente !== null, 'Usuarios DEMO existentes');
$area = DB::table('areas_postulacion')->where('id', $estudiante->area_postulacion_id)->first();
comprobar($area !== null && $area->codigo === 'C', 'Frontend guardó Ingeniería en MariaDB');
comprobar((int) $estudiante->seccion_id === (int) $docente->seccion_id, 'Alumno y docente comparten sección DEMO');

$simulacro = DB::table('simulacros')->where('nombre', 'DEMO CNI - Área C')->first();
comprobar($simulacro !== null && (int) $simulacro->total_preguntas === 20, 'Simulacro DEMO de 20 preguntas existe');
$intentos = DB::table('intentos')->where('usuario_id', $estudiante->id)
    ->where('simulacro_id', $simulacro->id)->get();
comprobar($intentos->count() === 1, 'Se persistió exactamente un intento desde Chromium');
$intento = $intentos->first();
comprobar(in_array($intento->estado, ['finalizado', 'enviado_auto'], true), 'Intento finalizado en backend');
comprobar($intento->puntaje_total !== null, 'Puntaje almacenado correctamente');
comprobar((int) $intento->total_preguntas === 20, 'Total del intento concuerda con el simulacro');

$respuestas = DB::table('respuestas_estudiante')->where('intento_id', $intento->id)->get();
comprobar($respuestas->count() === 20 && $respuestas->pluck('pregunta_id')->unique()->count() === 20, '20 preguntas sorteadas sin duplicados');
comprobar($respuestas->whereNotNull('alternativa_id')->count() === 1, 'Livewire guardó una alternativa y 19 respuestas en blanco');
comprobar($respuestas->every(fn ($r) => $r->puntaje_obtenido !== null), 'Calificación completa de todas las preguntas');

$cruzadas = DB::table('respuestas_estudiante as r')
    ->join('alternativas as a', 'a.id', '=', 'r.alternativa_id')
    ->where('r.intento_id', $intento->id)->whereColumn('r.pregunta_id', '<>', 'a.pregunta_id')
    ->count();
comprobar($cruzadas === 0, 'No existen respuestas cruzadas con preguntas ajenas');

$areas = DB::table('resultados_area')->where('intento_id', $intento->id)->get();
comprobar($areas->sum('total_preguntas') === 20, 'Resultados por asignatura totalizan 20 preguntas');
comprobar($areas->sum('aciertos') + $areas->sum('incorrectas') + $areas->sum('en_blanco') === 20,
    'Desglose de calificaciones íntegro');
comprobar((int) $intento->correctas + (int) $intento->incorrectas + (int) $intento->en_blanco + (int) $intento->doble_marca === 20,
    'Conteos finales del intento consistentes');
echo "[OK] Integración navegador / Livewire / Laravel / MariaDB verificada en entorno CI.\n";
