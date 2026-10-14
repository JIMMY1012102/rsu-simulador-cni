
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulacro_area', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('simulacro_id');
            $table->unsignedBigInteger('area_id');

            $table->enum('nivel', [
                'basico',
                'intermedio',
                'avanzado',
                'mixto'
            ])->default('mixto');

            $table->unsignedSmallInteger('cantidad_preguntas');

            $table->timestamps();

            $table->unique(
                ['simulacro_id', 'area_id', 'nivel'],
                'uq_simulacro_area_nivel'
            );

            $table->index(
                'area_id',
                'idx_simulacro_area_area'
            );

            $table->foreign(
                'simulacro_id',
                'fk_simulacro_area_simulacro'
            )
                ->references('id')
                ->on('simulacros')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(
                'area_id',
                'fk_simulacro_area_area'
            )
                ->references('id')
                ->on('areas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        DB::statement("
            ALTER TABLE simulacro_area
            ADD CONSTRAINT chk_simulacro_area_cantidad
            CHECK (cantidad_preguntas > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('simulacro_area');
    }
};
