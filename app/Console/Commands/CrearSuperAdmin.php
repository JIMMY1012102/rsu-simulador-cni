<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;

class CrearSuperAdmin extends Command
{
    protected $signature = 'cni:crear-superadmin
                            {--restablecer : Restablece la contraseña de un Super_Admin ya existente}';

    protected $description = 'Crea de forma segura la cuenta local Super_Admin para el panel administrativo CNI';

    public function handle(): int
    {
        // No instalar cuentas privilegiadas mediante este comando en producción.
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Este comando solo está habilitado en entornos local/testing.');
            return self::FAILURE;
        }

        $existente = Usuario::query()->where('usuario', 'Super_Admin')->first();

        if ($existente && $existente->rol !== 'admin') {
            $this->error('Ya existe Super_Admin con otro rol. No se modificarán sus permisos.');
            return self::FAILURE;
        }

        if ($existente && ! $this->option('restablecer')) {
            $this->info('La cuenta Super_Admin ya existe. No se cambió su contraseña.');
            $this->line('Para asignarle una contraseña nueva: php artisan cni:crear-superadmin --restablecer');
            return self::SUCCESS;
        }

        $contrasena = $this->secret('Contraseña de Super_Admin (se ocultará al escribir):');

        if (! is_string($contrasena) || strlen($contrasena) < 12) {
            $this->error('Escribe una contraseña de al menos 12 caracteres. No se creó ni modificó la cuenta.');
            return self::FAILURE;
        }

        if ($existente) {
            $existente->update(['password' => $contrasena, 'activo' => true]);
            $this->info('Contraseña de Super_Admin actualizada. Cuenta activada.');
        } else {
            Usuario::query()->create([
                'nombres' => 'Super',
                'apellidos' => 'Administrador CNI',
                'usuario' => 'Super_Admin',
                // Modelo Usuario tiene cast password => hashed (nunca texto plano).
                'password' => $contrasena,
                'rol' => 'admin',
                'activo' => true,
            ]);
            $this->info('Cuenta Super_Admin creada correctamente.');
        }

        $this->line('Activa CNI_ADMIN_ACADEMICO_ENABLED=true en tu .env y entra a /administracion.');
        return self::SUCCESS;
    }
}
