<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Área de postulación del examen de admisión (A, B, C, D o E).
 */
class AreaPostulacion extends Model
{
    protected $table = 'areas_postulacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function simulacros(): BelongsToMany
    {
        return $this->belongsToMany(
            Simulacro::class,
            'area_postulacion_simulacro',
            'area_postulacion_id',
            'simulacro_id'
        )->withTimestamps();
    }
}
