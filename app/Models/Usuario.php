<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo inicial compatible con la tabla "usuarios" del SQL de Sebastián.
 * Nia puede ampliar validaciones, relaciones y funcionalidades de autenticación.
 */
class Usuario extends Authenticatable
{
    use Notifiable;

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
}
