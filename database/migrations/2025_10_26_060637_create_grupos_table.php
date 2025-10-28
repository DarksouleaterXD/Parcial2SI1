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
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_materia')->constrained('materias')->onDelete('cascade');
            $table->foreignId('id_periodo')->constrained('periodos')->onDelete('cascade');
            $table->string('paralelo'); // A, B, C, etc.
            $table->enum('turno', ['mañana', 'tarde', 'noche'])->default('mañana');
            $table->integer('capacidad')->unsigned()->min(1)->max(500);
            $table->timestamps();

            // Índice único: cada grupo es único por materia + periodo + paralelo
            $table->unique(['id_materia', 'id_periodo', 'paralelo']);

            // Índices para búsquedas
            $table->index('id_materia');
            $table->index('id_periodo');
            $table->index('turno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
