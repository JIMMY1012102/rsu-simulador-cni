<?php

namespace App\Console\Commands;

use App\Services\LectorBancoExcel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Importa el banco de preguntas desde el Excel del colegio.
 *
 *   php artisan banco:importar "C:\ruta\Banco_preguntas_completo.xlsx" --simulacro
 *
 * Se puede ejecutar de nuevo cada vez que se actualice el Excel: las preguntas se
 * identifican por su código (AM_1, LE_12…) y se actualizan sin duplicarse.
 * Las imágenes se guardan en storage/app/public/banco (requiere `php artisan storage:link`).
 * Solo las preguntas con "Respuesta correcta" entran a los simulacros; el resto queda
 * guardado y aparece en cuanto se complete su respuesta en el Excel y se vuelva a importar.
 */
class ImportarBancoExcel extends Command
{
    public const SIMULACRO = 'Banco CNI · preguntas con respuesta';

    protected $signature = 'banco:importar
        {archivo : Ruta del Excel (.xlsx)}
        {--simulacro : Crea o actualiza un simulacro con todas las preguntas que ya tienen respuesta}';

    protected $description = 'Importa preguntas, imágenes y alternativas desde el Excel del banco CNI';

    /** Prefijo del código => [área (asignatura del sistema), bloque, tema]. */
    private const CURSOS = [
        'AM' => ['Razonamiento Matemático', 'habilidad_logico_matematica', 'Razonamiento matemático'],
        'AV' => ['Razonamiento Verbal', 'habilidad_verbal', 'Razonamiento verbal'],
        'IN' => ['Razonamiento Verbal', 'habilidad_verbal', 'Inglés'],
        'AL' => ['Matemática', 'habilidad_logico_matematica', 'Álgebra'],
        'AR' => ['Matemática', 'habilidad_logico_matematica', 'Aritmética'],
        'GE' => ['Matemática', 'habilidad_logico_matematica', 'Geometría'],
        'TR' => ['Matemática', 'habilidad_logico_matematica', 'Trigonometría'],
        'FI' => ['Ciencias', 'conocimientos', 'Física'],
        'QU' => ['Ciencias', 'conocimientos', 'Química'],
        'BI' => ['Ciencias', 'conocimientos', 'Biología'],
        'LE' => ['Humanidades', 'conocimientos', 'Lenguaje'],
        'LI' => ['Humanidades', 'conocimientos', 'Literatura'],
        'HP' => ['Humanidades', 'conocimientos', 'Historia del Perú'],
        'HU' => ['Humanidades', 'conocimientos', 'Historia universal'],
        'GG' => ['Humanidades', 'conocimientos', 'Geografía'],
        'EC' => ['Humanidades', 'conocimientos', 'Economía'],
        'CV' => ['Humanidades', 'conocimientos', 'Educación cívica'],
        'FL' => ['Humanidades', 'conocimientos', 'Filosofía y lógica'],
        'PS' => ['Humanidades', 'conocimientos', 'Psicología'],
        'CG' => ['Humanidades', 'conocimientos', 'Cultura general'],
    ];

    public function handle(LectorBancoExcel $lector): int
    {
        $archivo = $this->argument('archivo');
        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        $preguntas = $lector->leer($archivo);
        $desconocidos = collect($preguntas)->map(fn ($p) => $this->prefijo($p['codigo']))
            ->reject(fn ($prefijo) => isset(self::CURSOS[$prefijo]))->unique()->values();
        if ($desconocidos->isNotEmpty()) {
            $this->error('Códigos de curso sin configurar: '.$desconocidos->implode(', '));

            return self::FAILURE;
        }

        $disco = Storage::disk('public');
        $ahora = now();
        $creador = DB::table('usuarios')->where('rol', 'admin')->orderBy('id')->value('id');
        $temas = [];
        $conRespuesta = [];
        $incompletas = [];

        DB::transaction(function () use ($preguntas, $disco, $ahora, $creador, &$temas, &$conRespuesta, &$incompletas) {
            foreach ($preguntas as $p) {
                [$area, $bloque, $tema] = self::CURSOS[$this->prefijo($p['codigo'])];
                $temaId = $temas[$area.'|'.$tema] ??= $this->tema($area, $bloque, $tema, $ahora);

                $imagen = $p['imagen'] ? $this->guardarImagen($disco, $p['codigo'], $p['imagen']) : null;
                DB::table('preguntas')->updateOrInsert(['codigo' => $p['codigo']], [
                    'tema_id' => $temaId,
                    // La pregunta completa está en la imagen; el texto solo se usa si no hay imagen.
                    'enunciado' => $imagen ? '' : "Pregunta {$p['codigo']}",
                    'imagen' => $imagen,
                    'nivel' => 'intermedio',
                    'solucion' => '',
                    'activo' => true,
                    'creado_por' => $creador,
                    'updated_at' => $ahora,
                ]);
                $preguntaId = DB::table('preguntas')->where('codigo', $p['codigo'])->value('id');
                DB::table('preguntas')->where('id', $preguntaId)->whereNull('created_at')->update(['created_at' => $ahora]);

                $completa = true;
                foreach ($p['alternativas'] as $letra => $alternativa) {
                    $imagenAlt = $alternativa['imagen']
                        ? $this->guardarImagen($disco, "{$p['codigo']}_{$letra}", $alternativa['imagen']) : null;
                    $completa = $completa && ($alternativa['texto'] !== '' || $imagenAlt !== null);
                    // Se actualiza por letra para no romper respuestas ya guardadas por alumnos.
                    DB::table('alternativas')->updateOrInsert(
                        ['pregunta_id' => $preguntaId, 'letra' => $letra],
                        [
                            'texto' => mb_substr($alternativa['texto'], 0, 500),
                            'imagen' => $imagenAlt,
                            'es_correcta' => $p['respuesta'] === $letra,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ]
                    );
                }

                if (! $completa || ! $imagen) {
                    $incompletas[] = $p['codigo'];
                } elseif ($p['respuesta'] !== null) {
                    $conRespuesta[$area] = ($conRespuesta[$area] ?? 0) + 1;
                }
            }
        });

        $total = count($preguntas);
        $listas = array_sum($conRespuesta);
        $this->info("Importadas {$total} preguntas ({$listas} con respuesta, ".($total - $listas).' pendientes de respuesta).');
        if ($incompletas) {
            $this->warn('Revisar en el Excel (falta imagen o alguna alternativa): '.implode(', ', $incompletas));
        }

        if ($this->option('simulacro')) {
            $this->simulacro($conRespuesta, $creador, $ahora);
        }

        return self::SUCCESS;
    }

    private function prefijo(string $codigo): string
    {
        return strtoupper(strtok($codigo, '_'));
    }

    private function tema(string $area, string $bloque, string $tema, $ahora): int
    {
        DB::table('areas')->updateOrInsert(['nombre' => $area], ['bloque' => $bloque, 'activo' => true, 'updated_at' => $ahora]);
        $areaId = DB::table('areas')->where('nombre', $area)->value('id');
        DB::table('areas')->where('id', $areaId)->whereNull('created_at')->update(['created_at' => $ahora]);

        DB::table('temas')->updateOrInsert(['area_id' => $areaId, 'nombre' => $tema], ['activo' => true, 'updated_at' => $ahora]);
        $temaId = DB::table('temas')->where('area_id', $areaId)->where('nombre', $tema)->value('id');
        DB::table('temas')->where('id', $temaId)->whereNull('created_at')->update(['created_at' => $ahora]);

        return $temaId;
    }

    /** Devuelve la ruta pública que usa asset() en la vista del examen. */
    private function guardarImagen($disco, string $nombre, array $imagen): string
    {
        $ruta = "banco/{$nombre}.{$imagen['extension']}";
        $disco->put($ruta, $imagen['datos']);

        return "storage/{$ruta}";
    }

    /** Simulacro abierto a las cinco áreas con todas las preguntas que ya tienen respuesta. */
    private function simulacro(array $conRespuesta, ?int $creador, $ahora): void
    {
        $total = array_sum($conRespuesta);
        if ($total === 0) {
            $this->warn('No se creó el simulacro: ninguna pregunta tiene respuesta todavía.');

            return;
        }

        DB::table('simulacros')->updateOrInsert(['nombre' => self::SIMULACRO], [
            'tiempo_limite' => 60, 'total_preguntas' => $total, 'cantidad_alternativas' => 5,
            'fecha_inicio' => $ahora->copy()->subDay(), 'fecha_fin' => $ahora->copy()->addMonths(3),
            'activo' => true, 'creado_por' => $creador, 'updated_at' => $ahora,
        ]);
        $simulacroId = DB::table('simulacros')->where('nombre', self::SIMULACRO)->value('id');
        DB::table('simulacros')->where('id', $simulacroId)->whereNull('created_at')->update(['created_at' => $ahora]);

        DB::table('simulacro_area')->where('simulacro_id', $simulacroId)->delete();
        foreach ($conRespuesta as $area => $cantidad) {
            DB::table('simulacro_area')->insert([
                'simulacro_id' => $simulacroId,
                'area_id' => DB::table('areas')->where('nombre', $area)->value('id'),
                'nivel' => 'mixto', 'cantidad_preguntas' => $cantidad,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        foreach (DB::table('areas_postulacion')->pluck('id') as $areaPostulacionId) {
            DB::table('area_postulacion_simulacro')->updateOrInsert(
                ['area_postulacion_id' => $areaPostulacionId, 'simulacro_id' => $simulacroId],
                ['created_at' => $ahora, 'updated_at' => $ahora]
            );
        }

        $this->info("Simulacro «".self::SIMULACRO."» listo con {$total} preguntas, abierto a todas las áreas.");
    }
}
