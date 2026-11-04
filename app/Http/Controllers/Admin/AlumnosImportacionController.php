<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seccion;
use App\Models\Usuario;
use App\Services\LectorAlumnosExcel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class AlumnosImportacionController extends Controller
{
    public function index()
    {
        $secciones = Seccion::where('activo', true)->orderBy('grado')->orderBy('nombre')->get();
        $alumnos = Usuario::with('seccion')->where('rol', 'estudiante')->orderBy('apellidos')->orderBy('nombres')->paginate(30);
        return view('admin.alumnos', compact('secciones', 'alumnos'));
    }

    public function plantilla(LectorAlumnosExcel $lector)
    {
        return response()->download($lector->plantilla(), 'Plantilla_Alumnos_CNI.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function revisar(Request $request, LectorAlumnosExcel $lector): array
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:5120']]);
        try {
            $filas = $lector->leer($request->file('archivo')->getRealPath());
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['archivo' => $e instanceof \RuntimeException ? $e->getMessage() : 'Revisa la estructura y la hoja Alumnos del Excel.']);
        }
        $vistos = [];
        $errores = [];
        foreach ($filas as &$fila) {
            $validacion = Validator::make($fila, [
                'usuario' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
                'contrasena' => ['required', 'string', 'min:8', 'max:72'],
                'apellidos' => ['required', 'string', 'max:100'],
                'nombres' => ['required', 'string', 'max:100'],
                'grado' => ['required', 'string', 'max:20'],
                'seccion' => ['required', 'string', 'max:10'],
            ], [], ['contrasena' => 'contraseña', 'seccion' => 'sección']);
            foreach ($validacion->errors()->all() as $mensaje) {
                $errores[] = 'Fila '.$fila['fila'].': '.$mensaje;
            }
            // Normaliza para bloquear duplicados también en bases con colación insensible a mayúsculas.
            $clave = mb_strtolower($fila['usuario']);
            if (isset($vistos[$clave])) {
                $errores[] = 'Fila '.$fila['fila'].': usuario repetido en el archivo.';
            }
            $vistos[$clave] = true;
            if (Usuario::whereRaw('LOWER(usuario) = ?', [$clave])->exists()) {
                $errores[] = 'Fila '.$fila['fila'].': el usuario ya existe. No se modifican cuentas existentes.';
            }
            $seccion = Seccion::where('grado', $fila['grado'])->where('nombre', $fila['seccion'])->where('activo', true)->first();
            if (!$seccion) {
                $errores[] = 'Fila '.$fila['fila'].': grado y sección no corresponden a una sección activa. Revisa la lista disponible.';
            }
            $fila['seccion_id'] = $seccion?->id;
        }
        unset($fila);
        if ($errores) {
            throw ValidationException::withMessages(['archivo' => array_slice($errores, 0, 30)]);
        }
        return $filas;
    }

    public function validar(Request $request, LectorAlumnosExcel $lector)
    {
        $filas = $this->revisar($request, $lector);
        return response()->json([
            'total' => count($filas),
            'validacion' => Crypt::encryptString(json_encode([
                'proposito' => 'importar-alumnos',
                'usuario' => $request->user('web')->id,
                'hash' => hash_file('sha256', $request->file('archivo')->getRealPath()),
                'vence' => now()->addMinutes(15)->timestamp,
            ], JSON_THROW_ON_ERROR)),
        ]);
    }

    public function cargar(Request $request, LectorAlumnosExcel $lector)
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:5120']]);
        try {
            $token = json_decode(Crypt::decryptString((string) $request->input('validacion')), true, 512, JSON_THROW_ON_ERROR);
            $valido = ($token['proposito'] ?? '') === 'importar-alumnos'
                && ($token['usuario'] ?? null) === $request->user('web')->id
                && ($token['vence'] ?? 0) > now()->timestamp
                && hash_equals((string) ($token['hash'] ?? ''), hash_file('sha256', $request->file('archivo')->getRealPath()));
        } catch (Throwable $e) {
            $valido = false;
        }
        if (!$valido) {
            throw ValidationException::withMessages(['archivo' => 'Valida el archivo seleccionado antes de confirmar la importación.']);
        }
        try {
            $total = DB::transaction(function () use ($request, $lector) {
                $filas = $this->revisar($request, $lector);
                // Mantiene activas las secciones durante toda la inserción.
                $ids = array_unique(array_column($filas, 'seccion_id'));
                if (Seccion::whereIn('id', $ids)->where('activo', true)->lockForUpdate()->get()->count() !== count($ids)) {
                    throw ValidationException::withMessages(['archivo' => 'Una sección cambió. Valida otra vez el archivo.']);
                }
                foreach ($filas as $fila) {
                    Usuario::create([
                        'usuario' => $fila['usuario'], 'password' => Hash::make($fila['contrasena']),
                        'apellidos' => $fila['apellidos'], 'nombres' => $fila['nombres'],
                        'seccion_id' => $fila['seccion_id'], 'rol' => 'estudiante', 'activo' => true,
                    ]);
                }
                return count($filas);
            });
        } catch (QueryException $e) {
            // Nunca muestra consultas ni contraseñas en los errores de la interfaz.
            throw ValidationException::withMessages(['archivo' => 'No se importó ningún alumno. Revisa usuarios repetidos y secciones, y vuelve a validar.']);
        }
        return redirect()->route('admin.alumnos.index')->with('status', "Se agregaron {$total} alumnos con sus nombres, apellidos y sección.");
    }
}
