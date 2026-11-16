<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema (tabla "usuarios"): administradores, docentes y estudiantes.
 * Se autentica mediante el guard web y sesiones de Laravel.
 */
class Usuario extends Authenticatable
{
    use Notifiable;

    public const ROLES = ['admin', 'docente', 'estudiante'];

    protected $table = 'usuarios';

    protected $fillable = [
        'nombres',
        'apellidos',
        'usuario',
        'password',
        'rol',
        'seccion_id',
        'carrera_id',
        'area_postulacion_id',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }

    public function areaPostulacion(): BelongsTo
    {
        return $this->belongsTo(AreaPostulacion::class, 'area_postulacion_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
