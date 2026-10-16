
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('usuario_id');

            $table->unsignedBigInteger('carrera_id')
                ->nullable()
                ->comment('Carrera del estudiante al momento del intento');

            $table->unsignedBigInteger('simulacro_id')
                ->nullable()
                ->comment('Nulo en prácticas');

            $table->enum('tipo', ['simulacro', 'practica']);

            $table->unsignedBigInteger('area_id')
                ->nullable()
                ->comment('Área elegida en una práctica');

            $table->unsignedBigInteger('tema_id')
                ->nullable()
                ->comment('Tema elegido en una práctica');

            $table->dateTime('fecha_inicio');

            $table->dateTime('fecha_limite')
                ->nullable()
                ->comment('fecha_inicio + tiempo_limite; nulo en prácticas');

            $table->dateTime('fecha_fin')->nullable();

            $table->enum('estado', [
                'en_curso',
                'finalizado',
                'enviado_auto'
            ])->default('en_curso');

            $table->unsignedSmallInteger('total_preguntas');

            $table->decimal('puntaje_total', 8, 3)
                ->nullable()
                ->comment('Puede ser negativo por la penalización');

            $table->unsignedSmallInteger('correctas')->nullable();
            $table->unsignedSmallInteger('incorrectas')->nullable();
            $table->unsignedSmallInteger('en_blanco')->nullable();
            $table->unsignedSmallInteger('doble_marca')->nullable();

            $table->timestamps();

            $table->index(
                ['usuario_id', 'fecha_inicio'],
                'idx_intentos_usuario_fecha'
            );

            $table->index(
                ['simulacro_id', 'estado'],
                'idx_intentos_simulacro_estado'
            );

            $table->index(
                ['estado', 'fecha_limite'],
                'idx_intentos_estado_limite'
            );

            $table->index('carrera_id', 'idx_intentos_carrera');
            $table->index('area_id', 'idx_intentos_area');
            $table->index('tema_id', 'idx_intentos_tema');

            $table->foreign('usuario_id', 'fk_intentos_usuario')
                ->references('id')->on('usuarios')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('carrera_id', 'fk_intentos_carrera')
                ->references('id')->on('carreras')
                ->nullOnDelete()->cascadeOnUpdate();

            $table->foreign('simulacro_id', 'fk_intentos_simulacro')
                ->references('id')->on('simulacros')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('area_id', 'fk_intentos_area')
                ->references('id')->on('areas')
                ->nullOnDelete()->cascadeOnUpdate();

            $table->foreign('tema_id', 'fk_intentos_tema')
                ->references('id')->on('temas')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos');
    }
};
