<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Carrera universitaria, agrupada en un área de postulación (A–E).
 */
class Carrera extends Model
{
    protected $table = 'carreras';

    protected $fillable = [
        'area_postulacion_id',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function areaPostulacion(): BelongsTo
    {
        return $this->belongsTo(AreaPostulacion::class, 'area_postulacion_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'carrera_id');
    }
}
