
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();

            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('usuario', 50)->unique();
            $table->string('password', 255);

            $table->enum('rol', [
                'admin',
                'docente',
                'estudiante'
            ]);

            $table->foreignId('seccion_id')
                ->nullable()
                ->constrained('secciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('carrera_id')
                ->nullable()
                ->constrained('carreras')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['rol', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

