<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VaciarBancoPreguntasController extends Controller
{
    /**
     * Reinicio completo del banco DEMO. Se eliminan también los intentos,
     * respuestas y resultados porque dependen de los IDs de las preguntas.
     * Nunca modifica alumnos, usuarios, categorías ni simulacros.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user('web')?->rol === 'admin'
            && $request->user('web')?->usuario === 'Super_Admin',
            403
        );

        $request->validate([
            'confirmacion' => ['required', 'in:BORRAR TODO'],
        ], [
            'confirmacion.in' => 'Para confirmar, escribe exactamente BORRAR TODO.',
        ]);

        $totales = DB::transaction(function (): array {
            $totales = [
                'preguntas' => DB::table('preguntas')->count(),
                'intentos' => DB::table('intentos')->count(),
            ];

            // Respetamos las FK: primero los registros dependientes.
            // Incluye preguntas HIST_*, inactivas y las recién importadas.
            DB::table('respuestas_estudiante')->delete();
            DB::table('resultados_area')->delete();
            DB::table('intentos')->delete();
            DB::table('alternativas')->delete();
            DB::table('preguntas')->delete();

            return $totales;
        });

        // ALTER TABLE produce commit implícito en MariaDB, por eso va
        // después de terminar la transacción de borrado.
        $idsReiniciados = $this->reiniciarIds();
        $imagenesEliminadas = $this->borrarImagenesDelBanco();

        Log::notice('Reinicio completo del banco CNI DEMO', [
            'usuario_id' => $request->user('web')->id,
            'preguntas' => $totales['preguntas'],
            'intentos' => $totales['intentos'],
            'ids_reiniciados' => $idsReiniciados,
            'imagenes_eliminadas' => $imagenesEliminadas,
        ]);

        $mensaje = "Banco DEMO reiniciado: {$totales['preguntas']} preguntas y {$totales['intentos']} intentos eliminados, junto con respuestas y resultados de prueba.";
        if ($idsReiniciados) {
            $mensaje .= ' IDs reiniciados desde 1.';
        } else {
            $mensaje .= ' No se pudieron reiniciar todos los contadores de ID; revisa los permisos de la base de datos.';
        }
        if (! $imagenesEliminadas) {
            $mensaje .= ' Algunos archivos de imágenes no pudieron eliminarse; revisa storage/app/public.';
        }

        return redirect()->route('admin.preguntas.index')->with('status', $mensaje);
    }

    private function reiniciarIds(): bool
    {
        $tablas = [
            'respuestas_estudiante',
            'resultados_area',
            'intentos',
            'alternativas',
            'preguntas',
        ];

        try {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                DB::table('sqlite_sequence')->whereIn('name', $tablas)->delete();
                return true;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                foreach ($tablas as $tabla) {
                    // Nombres fijos, nunca derivados del request.
                    DB::statement("ALTER TABLE {$tabla} AUTO_INCREMENT = 1");
                }
                return true;
            }

            Log::warning('Motor de BD no compatible con reinicio de IDs', ['driver' => $driver]);
        } catch (Throwable $e) {
            Log::warning('El banco fue borrado, pero falló el reinicio de IDs', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    private function borrarImagenesDelBanco(): bool
    {
        $disco = Storage::disk('public');
        $correcto = true;

        // Estas carpetas son exclusivas de imágenes de preguntas importadas
        // y creadas manualmente. También borra las copias HIST_*.
        foreach (['banco', 'preguntas'] as $carpeta) {
            try {
                if ($disco->directoryExists($carpeta) && ! $disco->deleteDirectory($carpeta)) {
                    $correcto = false;
                }
            } catch (Throwable $e) {
                $correcto = false;
                Log::warning('No se pudo limpiar la carpeta del banco CNI', [
                    'carpeta' => $carpeta,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $correcto;
    }
}
