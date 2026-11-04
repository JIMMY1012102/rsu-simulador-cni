<?php

namespace App\Http\Requests\Simulacros;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Valida la creación (POST) y el reemplazo completo (PUT) de un simulacro.
 */
class GuardarSimulacroRequest extends FormRequest
{
    public const ROLES_GESTION = ['admin', 'docente'];

    public const NIVELES = ['basico', 'intermedio', 'avanzado', 'mixto'];

    public function authorize(): bool
    {
        return in_array($this->user()?->rol, self::ROLES_GESTION, true);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tiempo_limite' => ['required', 'integer', 'min:1', 'max:65535'],
            'total_preguntas' => ['required', 'integer', 'min:1', 'max:65535'],
            'cantidad_alternativas' => ['sometimes', 'integer', 'between:2,26'],
            'puntaje_correcta' => ['sometimes', 'numeric', 'gt:0', 'max:9999'],
            'puntaje_incorrecta' => ['sometimes', 'numeric', 'lte:0', 'min:-9999'],
            'puntaje_blanco' => ['sometimes', 'numeric', 'between:-9999,9999'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'activo' => ['sometimes', 'boolean'],

            'areas' => ['required', 'array', 'min:1'],
            'areas.*.area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('activo', true)],
            'areas.*.nivel' => ['sometimes', Rule::in(self::NIVELES)],
            'areas.*.cantidad_preguntas' => ['required', 'integer', 'min:1'],

            'carrera_id' => ['nullable', 'integer', Rule::exists('carreras', 'id')->where('activo', true)],
            'areas_postulacion' => ['sometimes', 'array'],
            'areas_postulacion.*' => ['integer', 'distinct', Rule::exists('areas_postulacion', 'id')->where('activo', true)],
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $areas = collect($this->input('areas', []));

                $suma = $areas->sum('cantidad_preguntas');
                if ($suma !== (int) $this->input('total_preguntas')) {
                    $validator->errors()->add(
                        'areas',
                        "La suma de cantidad_preguntas ({$suma}) debe ser igual a total_preguntas ({$this->input('total_preguntas')})."
                    );
                }

                $claves = $areas->map(fn ($a) => $a['area_id'].'|'.($a['nivel'] ?? 'mixto'));
                if ($claves->count() !== $claves->unique()->count()) {
                    $validator->errors()->add('areas', 'No se puede repetir la misma área con el mismo nivel.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'tiempo_limite' => 'tiempo límite',
            'total_preguntas' => 'total de preguntas',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'areas.*.area_id' => 'área',
            'areas.*.cantidad_preguntas' => 'cantidad de preguntas',
            'areas_postulacion.*' => 'área de postulación',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Los datos del simulacro no son válidos.',
            'errors' => $validator->errors(),
        ], 422));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Solo un administrador o docente puede crear o editar simulacros.',
            'errors' => (object) [],
        ], 403));
    }
}
