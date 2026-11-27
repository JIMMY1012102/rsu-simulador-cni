<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resultados\CalificarIntentoRequest;
use App\Services\ResultadoService;
use App\Services\Docente\ResultadosSeccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultadoController extends Controller
{
    public function __construct(
        private readonly ResultadoService $resultadoService,
        private readonly ResultadosSeccionService $resultadosSeccion
    ) {
    }

    public function calificar(
        CalificarIntentoRequest $request
    ): JsonResponse {
        $datos = $request->validated();

        $intentoId = (int) $datos['intento_id'];

        $intento = $this->resultadoService->obtenerIntento(
            $intentoId
        );

        $this->autorizarAcceso($request, $intento);

        $resultado = $this->resultadoService->calificar(
            $intentoId
        );

        return response()->json([
            'success' => true,
            'data' => $resultado,
            'message' => 'OK',
        ]);
    }

    public function mostrar(
        Request $request,
        int $intentoId
    ): JsonResponse {
        $intento = $this->resultadoService->obtenerIntento(
            $intentoId
        );

        $this->autorizarAcceso($request, $intento);

        $resultado = $this->resultadoService->mostrar(
            $intentoId
        );

        return response()->json([
            'success' => true,
            'data' => $resultado,
            'message' => 'OK',
        ]);
    }

    public function listar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        abort_unless($usuario, 401);

        $consulta = DB::table('intentos')
            ->whereNotNull('intentos.puntaje_total')
            ->orderByDesc('intentos.fecha_inicio');

        if ($usuario->rol === 'estudiante') {
            $consulta->where('intentos.usuario_id', $usuario->id);
        } elseif ($usuario->rol === 'docente') {
            abort_unless($this->resultadosSeccion->tieneSeccionActiva($usuario), 403);
            $consulta->join('usuarios as alumno', 'alumno.id', '=', 'intentos.usuario_id')
                ->where('alumno.rol', 'estudiante')
                ->where('alumno.activo', true)
                ->where('alumno.seccion_id', $usuario->seccion_id);
        } elseif ($usuario->rol !== 'admin') {
            abort(403, 'No autorizado.');
        }

        $resultados = $consulta->select(
            'intentos.id',
            'intentos.usuario_id',
            'intentos.simulacro_id',
            'intentos.fecha_inicio',
            'intentos.fecha_fin',
            'intentos.puntaje_total',
            'intentos.correctas',
            'intentos.incorrectas',
            'intentos.en_blanco',
            'intentos.doble_marca'
        )->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'message' => 'OK',
        ]);
    }

    private function autorizarAcceso(
        Request $request,
        object $intento
    ): void {
        $usuario = $request->user();

        abort_unless($usuario, 401);

        if ($usuario->rol === 'admin') {
            return;
        }

        if ($usuario->rol === 'docente') {
            abort_unless($this->resultadosSeccion->tieneSeccionActiva($usuario), 403);

            $pertenece = DB::table('usuarios')
                ->where('id', $intento->usuario_id)
                ->where('rol', 'estudiante')
                ->where('activo', true)
                ->where('seccion_id', $usuario->seccion_id)
                ->exists();

            abort_unless($pertenece, 403, 'El alumno no pertenece a su sección.');
            return;
        }

        abort_unless(
            $usuario->rol === 'estudiante'
                && (int) $usuario->id === (int) $intento->usuario_id,
            403,
            'No autorizado para acceder a este intento.'
        );
    }
}
