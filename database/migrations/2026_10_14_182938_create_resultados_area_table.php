
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_area', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('intento_id');
            $table->unsignedBigInteger('area_id');

            $table->unsignedSmallInteger('total_preguntas');
            $table->unsignedSmallInteger('aciertos');
            $table->unsignedSmallInteger('incorrectas');

            $table->unsignedSmallInteger('en_blanco')
                ->comment('Incluye doble marca');

            $table->decimal('puntaje', 8, 3);

            $table->decimal('porcentaje', 5, 2)
                ->comment('Porcentaje de aciertos del área');

            $table->timestamps();

            $table->unique(
                ['intento_id', 'area_id'],
                'uq_resultados_intento_area'
            );

            $table->index(
                'area_id',
                'idx_resultados_area'
            );

            $table->foreign(
                'intento_id',
                'fk_resultados_intento'
            )
                ->references('id')
                ->on('intentos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(
                'area_id',
                'fk_resultados_area'
            )
                ->references('id')
                ->on('areas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        DB::statement("
            ALTER TABLE resultados_area
            ADD CONSTRAINT chk_resultados_porcentaje
            CHECK (porcentaje BETWEEN 0 AND 100)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_area');
    }
};
