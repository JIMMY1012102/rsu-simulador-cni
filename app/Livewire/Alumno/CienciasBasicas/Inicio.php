<?php

namespace App\Livewire\Alumno\CienciasBasicas;

use App\Models\Usuario;
use App\Services\SimulacroService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Panel del área B «Ciencias Básicas · Inicio del simulador» (marco Figma 301:1600).
 * Módulo independiente: no reemplaza alumno.panel ni cambia la elección de área o carrera.
 * El acceso (estudiante del área B) lo controla el middleware SoloAreaCienciasBasicas.
 * El examen se inicia con examen.iniciar, la misma acción que usa el panel principal.
 */
class Inicio extends Component
{
    public const AREA = 'B';

    // Icono, flecha y tono del diseño para las materias que aparecen en Figma.
    private const ESTILOS = [
        'matematica' => ['calculator', 'matematica', 'azul'],
        'fisica' => ['atom-fisica', 'fisica', 'ambar'],
        'quimica' => ['flask-conical', 'quimica', 'rosa'],
        'biologia' => ['leaf', 'biologia', 'verde'],
        'razonamiento logico' => ['network', 'logico', 'violeta'],
        'estadistica' => ['chart-combined', 'estadistica', 'celeste'],
        'razonamiento verbal' => ['brain', 'verbal', 'naranja'],
        'tecnologia e investigacion' => ['monitor', 'tecnologia', 'fucsia'],
    ];

    private const MATERIAS_VISIBLES = 8;

    public bool $verTodas = false;

    public function render()
    {
        $usuario = $this->alumno();
        $area = $this->area($usuario);
        $ahora = now();

        $respuestas = fn () => DB::table('respuestas_estudiante as r')
            ->join('intentos as i', 'i.id', '=', 'r.intento_id')->where('i.usuario_id', $usuario->id);

        // «Resuelta» = calificada como correcta o incorrecta; en blanco y doble marca no cuentan.
        $resueltas = $respuestas()->whereIn('r.estado_respuesta', ['correcta', 'incorrecta'])->count();
        $correctas = $respuestas()->where('r.estado_respuesta', 'correcta')->count();

        // Avance = preguntas distintas resueltas sobre el banco activo.
        $banco = DB::table('preguntas')->where('activo', true)->count();
        $distintas = $respuestas()->whereIn('r.estado_respuesta', ['correcta', 'incorrecta'])
            ->distinct()->count('r.pregunta_id');

        $diasActivos = $respuestas()->whereNotNull('r.respondida_en')
            ->where('r.respondida_en', '>=', $ahora->copy()->subDays(400)->startOfDay())
            ->pluck('r.respondida_en')->map(fn ($fecha) => substr((string) $fecha, 0, 10))->flip();

        $disponibles = $this->simulacrosDisponibles($usuario, $area);
        $rendidos = DB::table('intentos')->where('usuario_id', $usuario->id)->where('tipo', 'simulacro')
            ->where('estado', '!=', 'en_curso')->pluck('simulacro_id');
        $simulacro = $disponibles->first(fn (array $s) => ! $rendidos->contains($s['id']));

        $materias = $this->materias($area);

        return view('livewire.alumno.ciencias-basicas.inicio', [
            'nombreCorto' => $this->nombreCorto($usuario),
            'area' => $area,
            'simulacro' => $simulacro,
            'enCurso' => $simulacro && $this->tieneIntentoAbierto($usuario, $simulacro['id']),
            'aviso' => $simulacro ? null : ($disponibles->isEmpty()
                ? 'No hay simulacros disponibles para tu área en este momento.'
                : 'Ya rendiste todos los simulacros disponibles para tu área.'),
            'resueltas' => $resueltas,
            'correctas' => $correctas,
            'acierto' => $resueltas ? (int) round($correctas * 100 / $resueltas) : null,
            'avance' => $banco ? min(100, (int) round($distintas * 100 / $banco)) : null,
            'racha' => $this->racha($diasActivos, $ahora),
            'semana' => collect(range(0, 6))->map(function (int $i) use ($ahora, $diasActivos) {
                $dia = $ahora->copy()->startOfWeek(Carbon::MONDAY)->addDays($i);

                return ['etiqueta' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][$i],
                    'hecho' => $diasActivos->has($dia->toDateString())];
            }),
            'materias' => $this->verTodas ? $materias : $materias->take(self::MATERIAS_VISIBLES),
            'hayMasMaterias' => $materias->count() > self::MATERIAS_VISIBLES,
        ])->layout('livewire.alumno.ciencias-basicas.layout', [
            'area' => $area,
            'nombre' => $this->nombreCorto($usuario),
            'campana' => $disponibles->isNotEmpty(),
        ]);
    }

    private function alumno(): Usuario
    {
        $usuario = Auth::guard('web')->user();
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);

        return $usuario;
    }

    /** Área B ya verificada por SoloAreaCienciasBasicas; se vuelve a comprobar en cada petición Livewire. */
    private function area(Usuario $usuario): object
    {
        $area = $usuario->areaPostulacion()->first() ?? $usuario->carrera()->first()?->areaPostulacion;
        abort_unless($area && $area->codigo === self::AREA, 403);

        return $area;
    }

    /** Misma regla que OrientacionController::panel: simulacro del área, de su carrera (si aplica) y vigente. */
    private function simulacrosDisponibles(Usuario $usuario, object $area): Collection
    {
        return app(SimulacroService::class)->listar($usuario)->filter(function (array $s) use ($usuario, $area) {
            $habilitado = collect($s['areas_postulacion'])->contains(fn (array $a) => (int) $a['id'] === (int) $area->id);
            $carreraPermitida = $s['carrera_id'] === null || (int) $usuario->carrera_id === (int) $s['carrera_id'];

            return $habilitado && $carreraPermitida && $s['activo'] && $s['fecha_inicio'] && $s['fecha_fin']
                && now()->gte($s['fecha_inicio']) && now()->lt($s['fecha_fin']);
        })->values();
    }

    /** Solo lectura: examen.iniciar reanuda el intento abierto o lo cierra si venció. */
    private function tieneIntentoAbierto(Usuario $usuario, int $simulacroId): bool
    {
        return DB::table('intentos')->where('usuario_id', $usuario->id)->where('simulacro_id', $simulacroId)
            ->where('estado', 'en_curso')
            ->where(fn ($q) => $q->whereNull('fecha_limite')->orWhere('fecha_limite', '>', now()))
            ->exists();
    }

    private function nombreCorto(Usuario $usuario): string
    {
        return trim(Str::before(trim($usuario->nombres).' ', ' ').' '.Str::before(trim($usuario->apellidos).' ', ' '));
    }

    /** Días consecutivos con respuestas, contando hasta hoy (o hasta ayer si hoy aún no respondió). */
    private function racha($diasActivos, Carbon $ahora): int
    {
        $dia = $ahora->copy()->startOfDay();
        if (! $diasActivos->has($dia->toDateString())) {
            $dia->subDay();
        }
        $racha = 0;
        while ($diasActivos->has($dia->toDateString())) {
            $racha++;
            $dia->subDay();
        }

        return $racha;
    }

    /**
     * Materias (tabla areas) que evalúan los simulacros activos del área B;
     * si aún no hay simulacros configurados para el área, se listan todas las materias activas.
     */
    private function materias(object $area): Collection
    {
        $delArea = DB::table('simulacro_area as sa')->join('simulacros as s', 's.id', '=', 'sa.simulacro_id')
            ->join('area_postulacion_simulacro as aps', 'aps.simulacro_id', '=', 's.id')
            ->where('s.activo', true)->where('aps.area_postulacion_id', $area->id)
            ->distinct()->pluck('sa.area_id');

        $filas = DB::table('areas as a')
            ->leftJoin('temas as t', fn ($j) => $j->on('t.area_id', '=', 'a.id')->where('t.activo', true))
            ->leftJoin('preguntas as p', fn ($j) => $j->on('p.tema_id', '=', 't.id')->where('p.activo', true))
            ->where('a.activo', true)->when($delArea->isNotEmpty(), fn ($q) => $q->whereIn('a.id', $delArea))
            ->groupBy('a.id', 'a.nombre')->orderBy('a.nombre')
            ->selectRaw('a.id, a.nombre, count(p.id) as total')->get();

        $ciclo = array_values(self::ESTILOS);

        return $filas->values()->map(function ($fila, int $i) use ($ciclo) {
            $clave = Str::of($fila->nombre)->ascii()->lower()->squish()->value();
            [$icono, $flecha, $tono] = self::ESTILOS[$clave] ?? $ciclo[$i % count($ciclo)];

            return ['id' => $fila->id, 'nombre' => $fila->nombre, 'preguntas' => (int) $fila->total,
                'icono' => $icono, 'flecha' => $flecha, 'tono' => $tono];
        });
    }
}
