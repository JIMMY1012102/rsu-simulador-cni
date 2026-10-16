
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resultados\CalificarIntentoRequest;
use App\Services\ResultadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultadoController extends Controller
{
    public function __construct(
        private readonly ResultadoService $resultadoService
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
            ->whereNotNull('puntaje_total')
            ->orderByDesc('fecha_inicio');

        if ($usuario->rol === 'estudiante') {
            $consulta->where(
                'usuario_id',
                $usuario->id
            );
        } elseif (!in_array(
            $usuario->rol,
            ['admin', 'docente'],
            true
        )) {
            abort(403, 'No autorizado.');
        }

        $resultados = $consulta->select(
            'id',
            'usuario_id',
            'simulacro_id',
            'fecha_inicio',
            'fecha_fin',
            'puntaje_total',
            'correctas',
            'incorrectas',
            'en_blanco',
            'doble_marca'
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

        if (in_array(
            $usuario->rol,
            ['admin', 'docente'],
            true
        )) {
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
