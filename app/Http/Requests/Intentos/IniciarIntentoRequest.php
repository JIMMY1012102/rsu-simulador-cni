<?php

namespace App\Http\Requests\Intentos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IniciarIntentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['simulacro', 'practica'])],
            'simulacro_id' => ['nullable', 'integer', 'exists:simulacros,id'],
            'carrera_id' => ['nullable', 'integer', 'exists:carreras,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'tema_id' => ['nullable', 'integer', 'exists:temas,id'],
        ];
    }
}
