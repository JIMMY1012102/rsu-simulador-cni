
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            $letra = $table->char('letra', 1);

            // SQLite (usado por phpunit) no tiene la colación ascii_bin.
            if (DB::getDriverName() === 'mysql') {
                $letra->charset('ascii')->collation('ascii_bin');
            }

            $table->string('texto', 500);
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();

            $table->unique(['pregunta_id', 'letra']);
        });

        // Blueprint no tiene check(); la restricción se agrega aparte y solo en MariaDB/MySQL.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE alternativas
                ADD CONSTRAINT chk_alternativas_letra
                CHECK (letra BETWEEN 'A' AND 'Z')
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativas');
    }
};
