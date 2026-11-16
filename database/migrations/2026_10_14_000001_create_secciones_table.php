
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 10);
            $table->string('grado', 20)->default('5to');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nombre', 'grado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};

