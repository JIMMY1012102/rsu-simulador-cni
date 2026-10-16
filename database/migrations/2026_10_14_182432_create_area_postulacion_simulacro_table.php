
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_postulacion_simulacro', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('area_postulacion_id');
            $table->unsignedBigInteger('simulacro_id');

            $table->timestamps();

            $table->unique(
                ['area_postulacion_id', 'simulacro_id'],
                'uq_ap_simulacro'
            );

            $table->index(
                'simulacro_id',
                'idx_ap_simulacro_simulacro'
            );

            $table->foreign(
                'area_postulacion_id',
                'fk_ap_simulacro_area'
            )
                ->references('id')
                ->on('areas_postulacion')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(
                'simulacro_id',
                'fk_ap_simulacro_simulacro'
            )
                ->references('id')
                ->on('simulacros')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_postulacion_simulacro');
    }
};
