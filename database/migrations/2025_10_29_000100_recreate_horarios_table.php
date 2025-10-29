<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la tabla existe y tiene la estructura antigua
        if (Schema::hasTable('horarios')) {
            // Eliminar la tabla antigua
            Schema::dropIfExists('horarios');
        }

        // Crear la tabla con la nueva estructura
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_grupo')->nullable()->constrained('grupos')->onDelete('cascade');
            $table->foreignId('id_aula')->nullable()->constrained('aulas')->onDelete('cascade');
            $table->foreignId('id_docente')->nullable()->constrained('docentes')->onDelete('cascade');
            $table->foreignId('id_bloque')->nullable()->constrained('bloques_horarios')->onDelete('cascade');
            $table->string('dia_semana', 20)->nullable(); // lunes, martes, etc.
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('id_grupo');
            $table->index('id_aula');
            $table->index('id_docente');
            $table->index('id_bloque');
            $table->index('dia_semana');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
