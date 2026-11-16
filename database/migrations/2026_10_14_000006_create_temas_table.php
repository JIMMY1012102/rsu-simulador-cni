
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')
                ->constrained('areas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['area_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temas');
    }
};
