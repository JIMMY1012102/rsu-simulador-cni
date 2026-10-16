<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario del sistema (tabla "usuarios"): administradores, docentes y estudiantes.
 * Se autentica con tokens de Sanctum desde el módulo de auth.
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

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

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
