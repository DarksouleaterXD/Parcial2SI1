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
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->comment('Código único de la materia');
            $table->string('nombre')->comment('Nombre de la materia');
            $table->foreignId('carrera_id')->nullable()->constrained('carreras')->onDelete('set null');
            $table->tinyInteger('horas_semana')->comment('Horas por semana');
            $table->boolean('activo')->default(true)->comment('Estado activo/inactivo');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo');
            $table->index('carrera_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
