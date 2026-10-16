<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila de la tabla "simulacro_area": cuántas preguntas de un área y nivel se sortean en un simulacro.
 * La relación con el modelo Area se agrega cuando el módulo de preguntas lo publique.
 */
class SimulacroArea extends Model
{
    protected $table = 'simulacro_area';

    protected $fillable = [
        'simulacro_id',
        'area_id',
        'nivel',
        'cantidad_preguntas',
    ];

    protected function casts(): array
    {
        return [
            'area_id' => 'integer',
            'cantidad_preguntas' => 'integer',
        ];
    }

    public function simulacro(): BelongsTo
    {
        return $this->belongsTo(Simulacro::class, 'simulacro_id');
    }
}
