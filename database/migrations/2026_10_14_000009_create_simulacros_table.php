<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulacros', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150);

            $table->unsignedSmallInteger('tiempo_limite')
                ->comment('Duración máxima en minutos');

            $table->unsignedSmallInteger('total_preguntas')
                ->default(100);

            $table->unsignedSmallInteger('cantidad_alternativas')
                ->default(4)
                ->comment('Cantidad de opciones por pregunta para este simulacro');

            $table->decimal('puntaje_correcta', 7, 3)
                ->default(20.000)
                ->comment('Puntos por respuesta correcta');

            $table->decimal('puntaje_incorrecta', 7, 3)
                ->default(-1.125)
                ->comment('Penalización por respuesta incorrecta');

            $table->decimal('puntaje_blanco', 7, 3)
                ->default(0.000)
                ->comment('Puntos por respuesta en blanco o doble marca');

            $table->decimal('puntaje_maximo', 12, 3)
                ->storedAs('total_preguntas * puntaje_correcta')
                ->comment('Calculado como total_preguntas x puntaje_correcta');

            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');

            $table->boolean('activo')->default(true);

            $table->unsignedBigInteger('creado_por')->nullable();

            $table->timestamps();

            $table->index(
                'creado_por',
                'idx_simulacros_creado_por'
            );

            $table->foreign(
                'creado_por',
                'fk_simulacros_creado_por'
            )
                ->references('id')
                ->on('usuarios')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE simulacros
                ADD CONSTRAINT chk_simulacros_tiempo
                CHECK (tiempo_limite > 0)
            ");

            DB::statement("
                ALTER TABLE simulacros
                ADD CONSTRAINT chk_simulacros_total
                CHECK (total_preguntas > 0)
            ");

            DB::statement("
                ALTER TABLE simulacros
                ADD CONSTRAINT chk_simulacros_alternativas
                CHECK (cantidad_alternativas BETWEEN 2 AND 26)
            ");

            DB::statement("
                ALTER TABLE simulacros
                ADD CONSTRAINT chk_simulacros_fechas
                CHECK (fecha_fin > fecha_inicio)
            ");

            DB::statement("
                ALTER TABLE simulacros
                ADD CONSTRAINT chk_simulacros_p_correcta
                CHECK (puntaje_correcta > 0)
            ");

            DB::statement("
                ALTER TABLE simulacros
                ADD CONSTRAINT chk_simulacros_p_incorrecta
                CHECK (puntaje_incorrecta <= 0)
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('simulacros');
    }
};
