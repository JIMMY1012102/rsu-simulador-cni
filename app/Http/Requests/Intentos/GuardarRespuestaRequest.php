<?php

namespace App\Http\Requests\Intentos;

use Illuminate\Foundation\Http\FormRequest;

class GuardarRespuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pregunta_id' => ['required', 'integer', 'exists:preguntas,id'],
            'alternativa_id' => ['nullable', 'integer', 'exists:alternativas,id'],
        ];
    }
}
