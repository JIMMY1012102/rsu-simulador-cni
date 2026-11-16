<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulacros\GuardarSimulacroRequest;
use App\Services\SimulacroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulacroController extends Controller
{
    public function __construct(private SimulacroService $servicio)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->exito($this->servicio->listar($request->user()));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $simulacro = $this->servicio->obtener($id, $request->user());

        return $simulacro ? $this->exito($simulacro) : $this->noEncontrado();
    }

    public function store(GuardarSimulacroRequest $request): JsonResponse
    {
        $simulacro = $this->servicio->crear($request->validated(), $request->user());

        return $this->exito($simulacro, 'Simulacro creado.', 201);
    }

    public function update(GuardarSimulacroRequest $request, int $id): JsonResponse
    {
        $simulacro = $this->servicio->actualizar($id, $request->validated());

        return $simulacro ? $this->exito($simulacro, 'Simulacro actualizado.') : $this->noEncontrado();
    }

    private function exito(mixed $data, string $mensaje = 'OK', int $estado = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $mensaje,
        ], $estado);
    }

    private function noEncontrado(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'El simulacro no existe.',
            'errors' => (object) [],
        ], 404);
    }
}
