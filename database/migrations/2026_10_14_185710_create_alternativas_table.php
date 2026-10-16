
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternativas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pregunta_id')
                ->constrained('preguntas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->char('letra', 1)
                ->charset('ascii')
                ->collation('ascii_bin');

            $table->string('texto', 500);
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();

            $table->unique(['pregunta_id', 'letra']);
            $table->check("letra BETWEEN 'A' AND 'Z'");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativas');
    }
};
