<?php

namespace App\Console\Commands;

use App\Services\LectorBancoExcel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Importa el banco de preguntas desde el Excel del colegio.
 *
 *   php artisan banco:importar "C:\ruta\Banco_preguntas_completo.xlsx" --simulacros
 *
 * Se puede ejecutar de nuevo cada vez que se actualice el Excel: las preguntas se
 * identifican por su código (AM_1, LE_12…) y se actualizan sin duplicarse.
 * Las imágenes se guardan en storage/app/public/banco (requiere `php artisan storage:link`).
 * Las preguntas sin "Respuesta correcta" también entran al examen, pero no se califican
 * hasta que se complete su respuesta en el Excel y se vuelva a importar.
 */
class ImportarBancoExcel extends Command
{
    public const PREFIJO_SIMULACRO = 'Simulacro CNI · Área ';

    protected $signature = 'banco:importar
        {archivo : Ruta del Excel (.xlsx)}
        {--simulacros : Crea o actualiza un simulacro de 100 preguntas para cada área (A–E)}';

    protected $description = 'Importa preguntas, imágenes y alternativas desde el Excel del banco CNI';

    /** Prefijo del código => [curso (asignatura del sistema), bloque del examen]. */
    private const CURSOS = [
        'AM' => ['Razonamiento Matemático', 'habilidad_logico_matematica'],
        'AV' => ['Razonamiento Verbal', 'habilidad_verbal'],
        'IN' => ['Inglés', 'habilidad_verbal'],
        'AR' => ['Aritmética', 'conocimientos'],
        'AL' => ['Álgebra', 'conocimientos'],
        'GE' => ['Geometría', 'conocimientos'],
        'TR' => ['Trigonometría', 'conocimientos'],
        'FI' => ['Física', 'conocimientos'],
        'QU' => ['Química', 'conocimientos'],
        'BI' => ['Biología', 'conocimientos'],
        'LE' => ['Lenguaje', 'conocimientos'],
        'LI' => ['Literatura', 'conocimientos'],
        'PS' => ['Psicología', 'conocimientos'],
        'CV' => ['Educación Cívica', 'conocimientos'],
        'EC' => ['Economía', 'conocimientos'],
        'HP' => ['Historia del Perú', 'conocimientos'],
        'HU' => ['Historia Universal', 'conocimientos'],
        'GG' => ['Geografía', 'conocimientos'],
        'FL' => ['Filosofía y Lógica', 'conocimientos'],
        'CG' => ['Cultura General', 'conocimientos'],
    ];

    /**
     * Preguntas por curso en el examen de cada área (100 en total).
     * Todas comparten la sección de Habilidades (20); el resto pesa según el área.
     * D sigue la distribución acordada para Ciencias Económicas y de Gestión.
     */
    public const DISTRIBUCION = [
        'A' => ['AM' => 10, 'AV' => 8, 'IN' => 2, 'AR' => 4, 'AL' => 4, 'GE' => 3, 'TR' => 2,
            'BI' => 20, 'QU' => 14, 'FI' => 8,
            'LE' => 7, 'LI' => 3, 'PS' => 5, 'CV' => 2, 'HP' => 2, 'HU' => 2, 'GG' => 2, 'EC' => 1, 'FL' => 1],
        'B' => ['AM' => 10, 'AV' => 8, 'IN' => 2, 'AR' => 6, 'AL' => 7, 'GE' => 6, 'TR' => 4,
            'FI' => 12, 'QU' => 12, 'BI' => 12,
            'LE' => 6, 'LI' => 3, 'PS' => 3, 'CV' => 2, 'HP' => 2, 'HU' => 2, 'GG' => 2, 'FL' => 1],
        'C' => ['AM' => 10, 'AV' => 8, 'IN' => 2, 'AR' => 6, 'AL' => 8, 'GE' => 8, 'TR' => 6,
            'FI' => 14, 'QU' => 10, 'BI' => 4,
            'LE' => 6, 'LI' => 3, 'PS' => 3, 'CV' => 2, 'HP' => 2, 'HU' => 2, 'GG' => 3, 'EC' => 1, 'FL' => 2],
        'D' => ['AM' => 10, 'AV' => 8, 'IN' => 2, 'AR' => 4, 'AL' => 4, 'GE' => 4, 'TR' => 2,
            'LE' => 8, 'LI' => 4, 'PS' => 6, 'CV' => 3,
            'EC' => 8, 'HP' => 3, 'HU' => 3, 'GG' => 4, 'FL' => 4,
            'FI' => 8, 'QU' => 7, 'BI' => 8],
        'E' => ['AM' => 10, 'AV' => 8, 'IN' => 2, 'AR' => 4, 'AL' => 3, 'GE' => 3, 'TR' => 1,
            'FI' => 3, 'QU' => 3, 'BI' => 4,
            'LE' => 10, 'LI' => 6, 'PS' => 5, 'CV' => 5, 'HP' => 6, 'HU' => 6, 'GG' => 6, 'EC' => 4, 'FL' => 6, 'CG' => 5],
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
        $disponibles = [];
        $conRespuesta = 0;
        $incompletas = [];

        DB::transaction(function () use ($preguntas, $disco, $ahora, $creador, &$temas, &$disponibles, &$conRespuesta, &$incompletas) {
            foreach ($preguntas as $p) {
                $prefijo = $this->prefijo($p['codigo']);
                [$curso, $bloque] = self::CURSOS[$prefijo];
                $temaId = $temas[$prefijo] ??= $this->tema($curso, $bloque, $ahora);

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

                $completa = $imagen !== null;
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

                if (! $completa) {
                    $incompletas[] = $p['codigo'];
                }
                $disponibles[$prefijo] = ($disponibles[$prefijo] ?? 0) + 1;
                $conRespuesta += $p['respuesta'] !== null ? 1 : 0;
            }
        });

        $total = count($preguntas);
        $this->info("Importadas {$total} preguntas ({$conRespuesta} con respuesta, ".($total - $conRespuesta).' aún sin respuesta).');
        if ($incompletas) {
            $this->warn('Revisar en el Excel (falta imagen o alguna alternativa): '.implode(', ', $incompletas));
        }

        if ($this->option('simulacros')) {
            return $this->simulacros($disponibles, $creador, $ahora);
        }

        return self::SUCCESS;
    }

    private function prefijo(string $codigo): string
    {
        return strtoupper(strtok($codigo, '_'));
    }

    /** Cada curso es una asignatura con un tema del mismo nombre, para sortear por curso. */
    private function tema(string $curso, string $bloque, $ahora): int
    {
        DB::table('areas')->updateOrInsert(['nombre' => $curso], ['bloque' => $bloque, 'activo' => true, 'updated_at' => $ahora]);
        $areaId = DB::table('areas')->where('nombre', $curso)->value('id');
        DB::table('areas')->where('id', $areaId)->whereNull('created_at')->update(['created_at' => $ahora]);

        DB::table('temas')->updateOrInsert(['area_id' => $areaId, 'nombre' => $curso], ['activo' => true, 'updated_at' => $ahora]);
        $temaId = DB::table('temas')->where('area_id', $areaId)->where('nombre', $curso)->value('id');
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

    /** Un simulacro de 100 preguntas por área de postulación, con la distribución de DISTRIBUCION. */
    private function simulacros(array $disponibles, ?int $creador, $ahora): int
    {
        $faltantes = [];
        foreach (self::DISTRIBUCION as $codigoArea => $cuotas) {
            foreach ($cuotas as $prefijo => $cantidad) {
                if (($disponibles[$prefijo] ?? 0) < $cantidad) {
                    $faltantes[] = "Área {$codigoArea}: ".self::CURSOS[$prefijo][0]." pide {$cantidad} y el Excel tiene ".($disponibles[$prefijo] ?? 0);
                }
            }
        }
        if ($faltantes) {
            $this->error("No se crearon los simulacros:\n".implode("\n", $faltantes));

            return self::FAILURE;
        }

        foreach (self::DISTRIBUCION as $codigoArea => $cuotas) {
            $areaPostulacionId = DB::table('areas_postulacion')->where('codigo', $codigoArea)->value('id');
            if ($areaPostulacionId === null) {
                $this->warn("No existe el área de postulación {$codigoArea}; se omite su simulacro.");

                continue;
            }

            $nombre = self::PREFIJO_SIMULACRO.$codigoArea;
            DB::table('simulacros')->updateOrInsert(['nombre' => $nombre], [
                'tiempo_limite' => 180, 'total_preguntas' => array_sum($cuotas), 'cantidad_alternativas' => 5,
                'fecha_inicio' => $ahora->copy()->subDay(), 'fecha_fin' => $ahora->copy()->addMonths(3),
                'activo' => true, 'creado_por' => $creador, 'updated_at' => $ahora,
            ]);
            $simulacroId = DB::table('simulacros')->where('nombre', $nombre)->value('id');
            DB::table('simulacros')->where('id', $simulacroId)->whereNull('created_at')->update(['created_at' => $ahora]);

            DB::table('simulacro_area')->where('simulacro_id', $simulacroId)->delete();
            foreach ($cuotas as $prefijo => $cantidad) {
                DB::table('simulacro_area')->insert([
                    'simulacro_id' => $simulacroId,
                    'area_id' => DB::table('areas')->where('nombre', self::CURSOS[$prefijo][0])->value('id'),
                    'nivel' => 'mixto', 'cantidad_preguntas' => $cantidad,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }

            DB::table('area_postulacion_simulacro')->where('simulacro_id', $simulacroId)->delete();
            DB::table('area_postulacion_simulacro')->insert([
                'area_postulacion_id' => $areaPostulacionId, 'simulacro_id' => $simulacroId,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);

            $this->info("«{$nombre}» listo: ".array_sum($cuotas).' preguntas.');
        }

        return self::SUCCESS;
    }
}
