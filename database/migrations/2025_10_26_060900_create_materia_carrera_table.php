<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carrera')->constrained('carreras')->onDelete('cascade');
            $table->foreignId('id_materia')->constrained('materias')->onDelete('cascade');
            $table->string('plan');
            $table->integer('semestre');
            $table->enum('tipo', ['teorica', 'practica', 'teoria-practica']);
            $table->integer('carga_teo')->default(0);
            $table->integer('carga_pra')->default(0);
            $table->timestamps();

            $table->unique(['id_carrera', 'id_materia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_carrera');
    }
};
