<?php

namespace App\Http\Requests\Preguntas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GuardarPreguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario !== null
            && in_array($usuario->rol, ['admin', 'docente'], true);
    }

    public function rules(): array
    {
        return [
            'tema_id' => ['required', 'integer', 'exists:temas,id'],
            'enunciado' => ['required', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'nivel' => ['required', 'in:basico,intermedio,avanzado'],
            'solucion' => ['required', 'string'],

            'alternativas' => ['required', 'array', 'min:2', 'max:26'],
            'alternativas.*.letra' => [
                'required', 'string', 'size:1', 'regex:/^[A-Z]$/'
            ],
            'alternativas.*.texto' => [
                'required', 'string', 'max:500'
            ],
            'alternativas.*.es_correcta' => [
                'required', 'boolean'
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $alternativas = $this->input('alternativas', []);

                if (!is_array($alternativas)) {
                    return;
                }

                $letras = [];
                $correctas = 0;

                foreach ($alternativas as $alternativa) {
                    if (!is_array($alternativa)) {
                        continue;
                    }

                    if (isset($alternativa['letra'])) {
                        $letras[] = $alternativa['letra'];
                    }

                    if (($alternativa['es_correcta'] ?? null) === true
                        || ($alternativa['es_correcta'] ?? null) === 1
                        || ($alternativa['es_correcta'] ?? null) === '1') {
                        $correctas++;
                    }
                }

                if (count($letras) !== count(array_unique($letras))) {
                    $validator->errors()->add(
                        'alternativas',
                        'Las letras de las alternativas no deben repetirse.'
                    );
                }

                if ($correctas !== 1) {
                    $validator->errors()->add(
                        'alternativas',
                        'Debe existir exactamente una alternativa correcta.'
                    );
                }
            },
        ];
    }
}

