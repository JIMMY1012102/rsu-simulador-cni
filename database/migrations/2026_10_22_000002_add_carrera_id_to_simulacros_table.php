<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('simulacros', function (Blueprint $table) {
            $table->foreignId('carrera_id')->nullable()
                ->constrained('carreras')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('simulacros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carrera_id');
        });
    }
};
