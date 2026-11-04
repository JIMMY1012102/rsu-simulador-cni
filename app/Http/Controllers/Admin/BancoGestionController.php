<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LectorBancoExcel;
use App\Support\PerfilExamenCni;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function plantilla(): BinaryFileResponse
    {
        return response()->download(
            resource_path('templates/plantilla-banco-cni.xlsx'),
            'Plantilla_Banco_Preguntas_CNI.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function revisarArchivo(Request $request, LectorBancoExcel $lector): array
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
        $codigos = array_column($filas, 'codigo');
        if (count($codigos) !== count(array_unique($codigos))) {
            throw ValidationException::withMessages(['archivo' => 'Hay códigos repetidos en el Excel. Usa un código único por pregunta.']);
        }
        foreach ($codigos as $codigo) {
            $prefijo = strtoupper(explode('_', $codigo, 2)[0]);
            if (! isset(PerfilExamenCni::CURSOS[$prefijo])) {
                throw ValidationException::withMessages(['archivo' => 'Código de curso no reconocido: '.$codigo]);
            }
        }

        return [$ruta, $filas];
    }

    private function comprobarHistorial(array $filas, bool $bloquear = false): void
    {
        $consulta = DB::table('preguntas')->whereIn('codigo', array_column($filas, 'codigo'))->orderBy('id');
        $ids = ($bloquear ? $consulta->lockForUpdate() : $consulta)->pluck('id');
        if ($ids->isNotEmpty() && DB::table('respuestas_estudiante')->whereIn('pregunta_id', $ids)->exists()) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo incluye preguntas usadas en exámenes. Usa códigos nuevos para sus nuevas versiones.',
            ]);
        }
    }

    public function validarExcel(Request $request, LectorBancoExcel $lector): JsonResponse
    {
        [$ruta, $filas] = $this->revisarArchivo($request, $lector);
        $this->comprobarHistorial($filas);
        $sinClave = 0;
        $incompletas = 0;
        foreach ($filas as $fila) {
            $sinClave += $fila['respuesta'] === null ? 1 : 0;
            $completa = $fila['imagen'] !== null;
            foreach ($fila['alternativas'] as $alternativa) {
                $completa = $completa && ($alternativa['texto'] !== '' || $alternativa['imagen'] !== null);
            }
            $incompletas += $completa ? 0 : 1;
        }

        return response()->json([
            'total' => count($filas),
            'sin_clave' => $sinClave,
            'incompletas' => $incompletas,
            'validacion' => Crypt::encryptString(json_encode([
                'hash' => hash_file('sha256', $ruta),
                'usuario' => $request->user('web')->id,
                'vence' => now()->addMinutes(15)->timestamp,
            ], JSON_THROW_ON_ERROR)),
        ]);
    }

    public function cargar(Request $request, LectorBancoExcel $lector): RedirectResponse
    {
        [$ruta, $filas] = $this->revisarArchivo($request, $lector);
        try {
            $validacion = json_decode(Crypt::decryptString((string) $request->input('validacion')), true, 512, JSON_THROW_ON_ERROR);
            $valida = ($validacion['usuario'] ?? null) === $request->user('web')->id
                && ($validacion['vence'] ?? 0) > now()->timestamp
                && hash_equals((string) ($validacion['hash'] ?? ''), hash_file('sha256', $ruta));
        } catch (Throwable $error) {
            $valida = false;
        }
        if (! $valida) {
            throw ValidationException::withMessages(['archivo' => 'Valida el archivo seleccionado antes de confirmar la importación.']);
        }
        // Revisa de nuevo el historial al confirmar, dentro de la transacción.

        $salida = DB::transaction(function () use ($filas, $ruta) {
            $this->comprobarHistorial($filas, true);
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
