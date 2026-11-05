
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_estudiante', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('intento_id');
            $table->unsignedBigInteger('pregunta_id');

            $table->unsignedSmallInteger('orden')
                ->comment('Posición aleatoria dentro del intento');

            $table->unsignedBigInteger('alternativa_id')
                ->nullable()
                ->comment('Nula si está en blanco o con doble marca');

            $table->enum('estado_respuesta', [
                'correcta',
                'incorrecta',
                'en_blanco',
                'doble_marca'
            ])
                ->nullable()
                ->comment('Nulo hasta calificar');

            $table->decimal('puntaje_obtenido', 7, 3)
                ->nullable()
                ->comment('+20.000, -1.125 o 0 por defecto');

            $table->dateTime('respondida_en')->nullable();

            $table->timestamps();

            $table->unique(
                ['intento_id', 'pregunta_id'],
                'uq_respuestas_intento_pregunta'
            );

            $table->unique(
                ['intento_id', 'orden'],
                'uq_respuestas_intento_orden'
            );

            $table->index(
                'pregunta_id',
                'idx_respuestas_pregunta'
            );

            $table->index(
                'alternativa_id',
                'idx_respuestas_alternativa'
            );

            $table->foreign(
                'intento_id',
                'fk_respuestas_intento'
            )
                ->references('id')
                ->on('intentos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(
                'pregunta_id',
                'fk_respuestas_pregunta'
            )
                ->references('id')
                ->on('preguntas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(
                'alternativa_id',
                'fk_respuestas_alternativa'
            )
                ->references('id')
                ->on('alternativas')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_estudiante');
    }
};
