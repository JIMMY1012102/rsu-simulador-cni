<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cifras del portal del alumno (Inicio y Temas de estudio): progreso, racha y avance por tema.
 * Todo sale de intentos y respuestas_estudiante; si el alumno aún no tiene datos, todo vale 0.
 */
class ProgresoAlumnoService
{
    private const DIAS_SEMANA = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    /** Barra de progreso y las tres cifras de la tarjeta "Tu progreso". */
    public function progreso(Usuario $usuario): array
    {
        // Se cuentan preguntas distintas: repetir una pregunta en otro intento no suma dos veces.
        $resueltas = $this->respuestasDe($usuario)
            ->whereNotNull('re.alternativa_id')->distinct()->count('re.pregunta_id');
        $correctas = $this->respuestasDe($usuario)
            ->where('re.estado_respuesta', 'correcta')->distinct()->count('re.pregunta_id');
        $totalPreguntas = DB::table('preguntas')->where('activo', true)->count();

        return [
            'resueltas' => $resueltas,
            'correctas' => $correctas,
            'total' => $totalPreguntas,
            'porcentaje' => $this->porcentaje($resueltas, $totalPreguntas),
            'acierto' => $this->porcentaje($correctas, $resueltas),
        ];
    }

    /**
     * Días seguidos con al menos una respuesta y los días de esta semana (Lun–Dom) en que estudió.
     * Si hoy todavía no respondió nada, la racha que viene de ayer sigue contando.
     */
    public function racha(Usuario $usuario): array
    {
        $hoy = Carbon::today();

        // DATE() funciona igual en MySQL/MariaDB y en SQLite (tests).
        $diasConActividad = $this->respuestasDe($usuario)
            ->whereNotNull('re.alternativa_id')
            ->where('re.respondida_en', '>=', $hoy->copy()->subYear())
            ->selectRaw('DATE(re.respondida_en) as dia')->distinct()
            ->pluck('dia')->flip();

        $dia = $diasConActividad->has($hoy->toDateString()) ? $hoy->copy() : $hoy->copy()->subDay();
        $diasSeguidos = 0;
        while ($diasConActividad->has($dia->toDateString())) {
            $diasSeguidos++;
            $dia->subDay();
        }

        $lunes = $hoy->copy()->startOfWeek(Carbon::MONDAY);
        $semana = collect(self::DIAS_SEMANA)->map(function (string $nombre, int $i) use ($lunes, $hoy, $diasConActividad) {
            $fecha = $lunes->copy()->addDays($i);

            return [
                'nombre' => $nombre,
                'estudio' => $diasConActividad->has($fecha->toDateString()),
                'es_hoy' => $fecha->isSameDay($hoy),
            ];
        });

        return ['dias' => $diasSeguidos, 'semana' => $semana];
    }

    /**
     * Temas activos con "X/Y preguntas"; el color y el ícono salen de config/alumno.php.
     * Sin $limite devuelve todos (página "Temas de estudio").
     */
    public function temas(Usuario $usuario, ?int $limite = null): Collection
    {
        $colores = config('alumno.colores_temas');

        return DB::table('temas as t')
            ->join('areas as a', 'a.id', '=', 't.area_id')
            ->where('t.activo', true)
            ->where('a.activo', true)
            ->orderBy('t.id')
            ->when($limite, fn (Builder $q) => $q->limit($limite))
            ->select('t.id', 't.nombre', 'a.nombre as area')
            ->selectSub(fn (Builder $q) => $q->from('preguntas as p')
                ->whereColumn('p.tema_id', 't.id')->where('p.activo', true)
                ->selectRaw('count(*)'), 'total')
            ->selectSub(fn (Builder $q) => $q->from('respuestas_estudiante as re')
                ->join('intentos as i', 'i.id', '=', 're.intento_id')
                ->join('preguntas as p', 'p.id', '=', 're.pregunta_id')
                ->whereColumn('p.tema_id', 't.id')
                ->where('p.activo', true)
                ->where('i.usuario_id', $usuario->id)
                ->whereNotNull('re.alternativa_id')
                ->selectRaw('count(distinct re.pregunta_id)'), 'resueltas')
            ->selectSub(fn (Builder $q) => $q->from('respuestas_estudiante as re')
                ->join('intentos as i', 'i.id', '=', 're.intento_id')
                ->join('preguntas as p', 'p.id', '=', 're.pregunta_id')
                ->whereColumn('p.tema_id', 't.id')
                ->where('p.activo', true)
                ->where('i.usuario_id', $usuario->id)
                ->where('re.estado_respuesta', 'correcta')
                ->selectRaw('count(distinct re.pregunta_id)'), 'correctas')
            ->get()
            ->values()
            ->map(fn (object $tema, int $i) => [
                'id' => $tema->id,
                'nombre' => $tema->nombre,
                'area' => $tema->area,
                'resueltas' => (int) $tema->resueltas,
                'correctas' => (int) $tema->correctas,
                'total' => (int) $tema->total,
                'porcentaje' => $this->porcentaje((int) $tema->resueltas, (int) $tema->total),
                'acierto' => $this->porcentaje((int) $tema->correctas, (int) $tema->resueltas),
                'icono' => $this->iconoDelTema($tema->nombre),
                'color' => $colores[$i % count($colores)],
            ]);
    }

    /** Porcentaje entero entre 0 y 100; evita dividir entre cero. */
    public function porcentaje(int $parte, int $total): int
    {
        return $total > 0 ? (int) min(100, round($parte * 100 / $total)) : 0;
    }

    /**
     * Respuestas del alumno en todos sus intentos.
     * "Resuelta" = la pregunta tiene una alternativa marcada en algún intento.
     */
    private function respuestasDe(Usuario $usuario): Builder
    {
        return DB::table('respuestas_estudiante as re')
            ->join('intentos as i', 'i.id', '=', 're.intento_id')
            ->where('i.usuario_id', $usuario->id);
    }

    private function iconoDelTema(string $nombre): string
    {
        $texto = Str::lower(Str::ascii($nombre));

        foreach (config('alumno.iconos_temas') as $palabra => $icono) {
            if (str_contains($texto, $palabra)) {
                return $icono;
            }
        }

        return config('alumno.icono_tema_por_defecto');
    }
}
