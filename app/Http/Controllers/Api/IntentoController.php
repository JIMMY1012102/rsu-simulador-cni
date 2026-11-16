<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Intentos\GuardarRespuestaRequest;
use App\Http\Requests\Intentos\IniciarIntentoRequest;
use App\Services\IntentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntentoController extends Controller
{
    public function __construct(
        private readonly IntentoService $intentoService
    ) {}

    public function iniciar(IniciarIntentoRequest $request): JsonResponse
    {
        $intento = $this->intentoService->iniciar(
            (int) $request->user()->getAuthIdentifier(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $intento,
            'message' => 'Intento iniciado correctamente.',
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $usuarioId = (int) $request->user()->getAuthIdentifier();

        $intento = $this->intentoService->obtenerPropio($id, $usuarioId);

        $respuestas = DB::table('respuestas_estudiante')
            ->where('intento_id', $id)
            ->orderBy('orden')
            ->get([
                'id',
                'pregunta_id',
                'orden',
                'alternativa_id',
                'respondida_en',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'intento' => $intento,
                'preguntas' => $this->intentoService->preguntas($id),
                'respuestas' => $respuestas,
            ],
            'message' => 'OK',
        ]);
    }

    public function guardarRespuesta(
        GuardarRespuestaRequest $request,
        int $id
    ): JsonResponse {
        $respuesta = $this->intentoService->guardarRespuesta(
            $id,
            (int) $request->user()->getAuthIdentifier(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $respuesta,
            'message' => 'Respuesta guardada correctamente.',
        ]);
    }

    public function finalizar(Request $request, int $id): JsonResponse
    {
        $intento = $this->intentoService->finalizar(
            $id,
            (int) $request->user()->getAuthIdentifier()
        );

        return response()->json([
            'success' => true,
            'data' => $intento,
            'message' => 'Intento finalizado correctamente.',
        ]);
    }
}
