<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Preguntas\GuardarPreguntaRequest;
use App\Services\PreguntaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreguntaController extends Controller
{
    public function __construct(
        private PreguntaService $service
    ) {}

    
public function index(Request $request): JsonResponse
{
    $preguntas = $this->service->listar();

    if ($request->user()?->rol === 'estudiante') {
        $preguntas->getCollection()->transform(function ($pregunta) {
            unset($pregunta->solucion);
            return $pregunta;
        });
    }

    return response()->json([
        'success' => true,
        'data' => $preguntas,
        'message' => 'Preguntas obtenidas correctamente',
    ]);
}

public function show(int $id, Request $request): JsonResponse
{
    $pregunta = $this->service->obtener($id);

    if (!$pregunta) {
        return $this->noEncontrada();
    }

    $rol = $request->user()?->rol;

    if (!in_array($rol, ['admin', 'docente', 'estudiante'], true)) {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'No autorizado',
            'errors' => new \stdClass(),
        ], 403);
    }

    $consulta = DB::table('alternativas')
        ->where('pregunta_id', $id)
        ->orderBy('letra');

    if ($rol === 'estudiante') {
        unset($pregunta->solucion);

        $consulta->select(
            'id',
            'letra',
            'texto'
        );
    }

    $pregunta->alternativas = $consulta->get();

    return response()->json([
        'success' => true,
        'data' => $pregunta,
        'message' => 'Pregunta encontrada',
    ]);
}



    


    public function store(
        GuardarPreguntaRequest $request
    ): JsonResponse {
        $id = $this->service->guardar(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Pregunta registrada correctamente',
        ], 201);
    }

    public function update(
        GuardarPreguntaRequest $request,
        int $id
    ): JsonResponse {
        $actualizada = $this->service->actualizar(
            $id,
            $request->validated()
        );

        if (!$actualizada) {
            return $this->noEncontrada();
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Pregunta actualizada correctamente',
        ]);
    }

public function destroy(int $id, Request $request): JsonResponse
{
    $usuario = $request->user();

    if (!$usuario || !in_array($usuario->rol, ['admin', 'docente'], true)) {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'No tienes permisos para eliminar preguntas',
            'errors' => new \stdClass(),
        ], 403);
    }

    if (!$this->service->eliminar($id)) {
        return $this->noEncontrada();
    }

    return response()->json([
        'success' => true,
        'data' => ['id' => $id],
        'message' => 'Pregunta desactivada correctamente',
    ]);
}

    

    private function noEncontrada(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Pregunta no encontrada',
            'errors' => new \stdClass(),
        ], 404);
    }
}

