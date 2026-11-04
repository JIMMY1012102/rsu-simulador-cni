<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VaciarBancoPreguntasController extends Controller
{
    /**
     * Retira el banco para permitir una nueva importación.
     * Las preguntas usadas en intentos se archivan, sin alterar las respuestas
     * ni las calificaciones históricas (FK restrictiva).
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // En el sistema, Super_Admin es una cuenta con rol "admin", no un rol separado.
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

        [$eliminadas, $archivadas] = DB::transaction(function () {
            $preguntas = DB::table('preguntas')
                ->where(function ($query) {
                    $query->whereNull('codigo')->orWhere('codigo', 'not like', 'HIST_%');
                })
                ->orderBy('id')->lockForUpdate()
                ->get(['id', 'codigo', 'imagen']);

            if ($preguntas->isEmpty()) {
                return [0, 0];
            }

            $ids = $preguntas->pluck('id');

            // No alterar preguntas que un estudiante todavía está respondiendo.
            $enCurso = DB::table('respuestas_estudiante as r')
                ->join('intentos as i', 'i.id', '=', 'r.intento_id')
                ->whereIn('r.pregunta_id', $ids)
                ->where('i.estado', 'en_curso')
                ->exists();

            if ($enCurso) {
                throw ValidationException::withMessages([
                    'confirmacion' => 'No se puede vaciar el banco mientras haya exámenes en curso. Finaliza esos intentos primero.',
                ]);
            }

            $usadas = DB::table('respuestas_estudiante')
                ->whereIn('pregunta_id', $ids)
                ->distinct()
                ->pluck('pregunta_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true]);

            $sinUso = [];
            $archivadas = 0;

            foreach ($preguntas as $pregunta) {
                if (! isset($usadas[(int) $pregunta->id])) {
                    $sinUso[] = (int) $pregunta->id;
                    continue;
                }

                // El importador reutiliza nombres banco/CODIGO.png. Se conserva una
                // copia independiente para que otra importación no altere el examen anterior.
                $imagen = $this->conservarImagen($pregunta->imagen, (int) $pregunta->id);
                $alternativas = DB::table('alternativas')
                    ->where('pregunta_id', $pregunta->id)->get(['id', 'imagen']);
                foreach ($alternativas as $alternativa) {
                    $imagenAlternativa = $this->conservarImagen($alternativa->imagen, (int) $pregunta->id);
                    if ($imagenAlternativa !== $alternativa->imagen) {
                        DB::table('alternativas')->where('id', $alternativa->id)
                            ->update(['imagen' => $imagenAlternativa, 'updated_at' => now()]);
                    }
                }

                // Código histórico exclusivo: libera el código original (p. ej. AM_1)
                // para volver a importar una pregunta nueva sin tocar el intento anterior.
                DB::table('preguntas')->where('id', $pregunta->id)->update([
                    'codigo' => 'HIST_'.$pregunta->id,
                    'imagen' => $imagen,
                    'activo' => false,
                    'updated_at' => now(),
                ]);
                $archivadas++;
            }

            foreach (array_chunk($sinUso, 500) as $grupo) {
                // Las alternativas sin historial se eliminan por ON DELETE CASCADE.
                DB::table('preguntas')->whereIn('id', $grupo)->delete();
            }

            return [count($sinUso), $archivadas];
        });

        logger()->notice('Banco de preguntas CNI vaciado por Super_Admin', [
            'usuario_id' => $request->user('web')->id,
            'eliminadas' => $eliminadas,
            'archivadas' => $archivadas,
        ]);

        return redirect()->route('admin.preguntas.index')->with(
            'status',
            "Banco vaciado: {$eliminadas} preguntas eliminadas y {$archivadas} archivadas para conservar exámenes anteriores. Ya puedes importar nuevas preguntas."
        );
    }

    private function conservarImagen(?string $imagen, int $preguntaId): ?string
    {
        // Las imágenes manuales de storage/preguntas/ son únicas; solo hay
        // riesgo de sobrescritura en los archivos de la importación Excel.
        if (! $imagen || ! str_starts_with($imagen, 'storage/banco/')) {
            return $imagen;
        }

        $origen = substr($imagen, strlen('storage/'));
        $disco = Storage::disk('public');
        if (! $disco->exists($origen)) {
            return $imagen;
        }

        $destino = 'banco/historico/'.$preguntaId.'/'.basename($origen);
        if (! $disco->copy($origen, $destino)) {
            throw new RuntimeException('No se pudo preservar la imagen histórica. El banco no fue modificado.');
        }

        return 'storage/'.$destino;
    }
}
