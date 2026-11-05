<?php

namespace App\Http\Requests\Resultados;

use Illuminate\Foundation\Http\FormRequest;

class CalificarIntentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'intento_id' => [
                'required',
                'integer',
                'min:1',
                'exists:intentos,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'intento_id.required' => 'El ID del intento es obligatorio.',
            'intento_id.integer' => 'El ID debe ser un nÃºmero entero.',
            'intento_id.min' => 'El ID debe ser mayor que cero.',
            'intento_id.exists' => 'El intento no existe.',
        ];
    }
}
