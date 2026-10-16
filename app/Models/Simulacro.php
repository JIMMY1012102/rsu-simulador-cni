<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configuración de un simulacro (tabla "simulacros").
 * "puntaje_maximo" es una columna generada por la base de datos, por eso no es asignable.
 */
class Simulacro extends Model
{
    protected $table = 'simulacros';

    protected $fillable = [
        'nombre',
        'tiempo_limite',
        'total_preguntas',
        'cantidad_alternativas',
        'puntaje_correcta',
        'puntaje_incorrecta',
        'puntaje_blanco',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'tiempo_limite' => 'integer',
            'total_preguntas' => 'integer',
            'cantidad_alternativas' => 'integer',
            'puntaje_correcta' => 'decimal:3',
            'puntaje_incorrecta' => 'decimal:3',
            'puntaje_blanco' => 'decimal:3',
            'puntaje_maximo' => 'decimal:3',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    /**
     * Cantidad de preguntas por área y nivel (tabla "simulacro_area").
     */
    public function distribucion(): HasMany
    {
        return $this->hasMany(SimulacroArea::class, 'simulacro_id');
    }

    /**
     * Áreas de postulación (A–E) para las que está abierto el simulacro.
     */
    public function areasPostulacion(): BelongsToMany
    {
        return $this->belongsToMany(
            AreaPostulacion::class,
            'area_postulacion_simulacro',
            'simulacro_id',
            'area_postulacion_id'
        )->withTimestamps();
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }
}
