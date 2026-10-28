<?php

namespace App\Services\Alumno;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Progreso e historial exclusivamente del estudiante autenticado. */
class HistorialEstudianteService
{
    public function resumen(Usuario $usuario): array
    {
        $consulta = DB::table('intentos')->where('usuario_id', $usuario->id)
            ->whereNotNull('puntaje_total');
        $agregados = (clone $consulta)->selectRaw('COUNT(*) as total, MAX(puntaje_total) as mejor, AVG(puntaje_total) as promedio')->first();
        $ultimo = (clone $consulta)->orderByDesc('fecha_inicio')->orderByDesc('id')->first(['id', 'puntaje_total']);
        $ultimos = (clone $consulta)->orderByDesc('fecha_inicio')->orderByDesc('id')
            ->limit(24)->get(['id', 'puntaje_total', 'fecha_inicio'])->reverse()->values();

        $minimo = min(0, (float) ($ultimos->min('puntaje_total') ?? 0));
        $maximo = max(1, (float) ($ultimos->max('puntaje_total') ?? 1));
        if ($maximo - $minimo < 1) {
            $maximo = $minimo + 1;
        }
        $puntos = [];
        $serie = [];
        $n = $ultimos->count();
        foreach ($ultimos as $index => $item) {
            $x = $n === 1 ? 490.0 : 50.0 + 880.0 * $index / ($n - 1);
            $y = 252.0 - (((float) $item->puntaje_total - $minimo) / ($maximo - $minimo)) * 216.0;
            $puntos[] = sprintf('%.2f,%.2f', $x, $y);
            $serie[] = ['id' => (int) $item->id, 'puntaje' => (float) $item->puntaje_total,
                'x' => round($x, 2), 'y' => round($y, 2)];
        }

        return [
            'total' => (int) $agregados->total,
            'mejor' => $agregados->mejor === null ? null : (float) $agregados->mejor,
            'ultimo' => $ultimo ? (float) $ultimo->puntaje_total : null,
            'promedio' => $agregados->promedio === null ? null : (float) $agregados->promedio,
            'minEje' => $minimo,
            'maxEje' => $maximo,
            'serie' => $serie,
            'puntos' => implode(' ', $puntos),
        ];
    }

    public function historial(Usuario $usuario, Request $request): array
    {
        $consulta = DB::table('intentos as i')
            ->leftJoin('simulacros as s', 's.id', '=', 'i.simulacro_id')
            ->where('i.usuario_id', $usuario->id)
            ->select('i.id', 'i.fecha_inicio', 'i.tipo', 'i.estado', 'i.puntaje_total', 's.nombre as simulacro_nombre');

        $buscar = $request->query('buscar');
        if (is_string($buscar) && $buscar !== '') {
            if (! ctype_digit($buscar)) {
                $consulta->whereRaw('1 = 0');
            } else {
                $consulta->where('i.id', (int) $buscar);
            }
        }
        if ($request->query('tipo') === 'simulacro' || $request->query('tipo') === 'practica') {
            $consulta->where('i.tipo', $request->query('tipo'));
        }
        if ($request->query('estado') === 'calificado') {
            $consulta->whereNotNull('i.puntaje_total');
        } elseif ($request->query('estado') === 'en_curso') {
            $consulta->whereNull('i.puntaje_total');
        }

        $intentos = $consulta->orderByDesc('i.fecha_inicio')->orderByDesc('i.id')
            ->paginate(12)->withQueryString();

        // Diferencias reales respecto al intento calificado anterior del mismo alumno.
        $previo = null;
        $cambios = [];
        $calificados = DB::table('intentos')->where('usuario_id', $usuario->id)
            ->whereNotNull('puntaje_total')->orderBy('fecha_inicio')->orderBy('id')
            ->get(['id', 'puntaje_total']);
        foreach ($calificados as $item) {
            $actual = (float) $item->puntaje_total;
            $cambios[$item->id] = $previo === null ? null : round($actual - $previo, 2);
            $previo = $actual;
        }
        $stats = $this->resumen($usuario);
        return ['intentos' => $intentos, 'cambios' => $cambios, 'totales' => [
            'intentos' => DB::table('intentos')->where('usuario_id', $usuario->id)->count(),
            'mejor' => $stats['mejor'],
            'ultimo' => $stats['ultimo'],
            'promedio' => $stats['promedio'],
        ]];
    }
}
