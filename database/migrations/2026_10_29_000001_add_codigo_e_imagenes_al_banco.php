<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para el banco importado desde Excel:
 * - preguntas.codigo (AM_1, LE_12…) permite volver a importar sin duplicar.
 * - alternativas.imagen guarda las alternativas que vienen como imagen.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->string('codigo', 30)->nullable()->unique()->after('id');
        });

        Schema::table('alternativas', function (Blueprint $table) {
            $table->string('imagen', 255)->nullable()->after('texto');
        });
    }

    public function down(): void
    {
        Schema::table('alternativas', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });

        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
