<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LectorBancoExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;
use ZipArchive;

class BancoGestionController extends Controller
{
    public function subtemas(): View
    {
        $temas = DB::table('temas as t')
            ->join('areas as a', 'a.id', '=', 't.area_id')
            ->leftJoin('preguntas as p', function ($join) {
                $join->on('p.tema_id', '=', 't.id')->where('p.activo', true);
            })
            ->select('t.id', 't.nombre', 't.activo', 'a.nombre as curso', 'a.activo as curso_activo')
            ->selectRaw('COUNT(p.id) as preguntas_activas')
            ->groupBy('t.id', 't.nombre', 't.activo', 'a.nombre', 'a.activo')
            ->orderBy('a.nombre')->orderBy('t.nombre')->paginate(30);

        return view('admin.subtemas', compact('temas'));
    }

    public function importar(): View
    {
        return view('admin.importar');
    }

    public function cargar(Request $request, LectorBancoExcel $lector): RedirectResponse
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:51200']]);
        $ruta = $request->file('archivo')->getRealPath();
        $zip = new ZipArchive;
        if ($zip->open($ruta) !== true) {
            throw ValidationException::withMessages(['archivo' => 'No se pudo abrir el archivo Excel.']);
        }
        $tamano = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $tamano += $zip->statIndex($i)['size'] ?? 0;
        }
        $zip->close();
        if ($tamano > 250 * 1024 * 1024) {
            throw ValidationException::withMessages(['archivo' => 'El contenido del Excel es demasiado grande.']);
        }

        try {
            $filas = $lector->leer($ruta);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['archivo' => 'Revisa la hoja «Banco de preguntas» y sus encabezados.']);
        }
        if (! $filas) {
            throw ValidationException::withMessages(['archivo' => 'El Excel no contiene filas de preguntas.']);
        }
        // Mantiene el historial: no sobrescribir códigos de preguntas ya sorteadas.
        $salida = DB::transaction(function () use ($filas, $ruta) {
            $ids = DB::table('preguntas')->whereIn('codigo', array_column($filas, 'codigo'))
                ->orderBy('id')->lockForUpdate()->pluck('id');
            if ($ids->isNotEmpty() && DB::table('respuestas_estudiante')->whereIn('pregunta_id', $ids)->exists()) {
                throw ValidationException::withMessages([
                    'archivo' => 'El archivo incluye preguntas usadas en exámenes. Usa códigos nuevos para sus nuevas versiones.',
                ]);
            }
            $estado = Artisan::call('banco:importar', ['archivo' => $ruta, '--no-ansi' => true]);
            $salida = Artisan::output();
            if ($estado !== 0) {
                throw ValidationException::withMessages(['archivo' => trim($salida) ?: 'No se pudo importar el archivo.']);
            }

            return trim($salida);
        });

        return redirect()->route('admin.importar.index')
            ->with('status', 'Excel importado al banco de preguntas.')
            ->with('importacion', $salida);
    }
}
