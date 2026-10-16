
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tema_id')
                ->constrained('temas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->text('enunciado');
            $table->string('imagen', 255)->nullable();

            $table->enum('nivel', [
                'basico',
                'intermedio',
                'avanzado',
            ]);

            $table->text('solucion');
            $table->boolean('activo')->default(true);

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index(['tema_id', 'nivel', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
