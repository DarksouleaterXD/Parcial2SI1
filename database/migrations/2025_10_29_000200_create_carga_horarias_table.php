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
        Schema::create('carga_horarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_docente')->constrained('docentes')->onDelete('cascade');
            $table->foreignId('id_grupo')->constrained('grupos')->onDelete('cascade');
            $table->foreignId('id_periodo')->constrained('periodos')->onDelete('cascade');
            $table->integer('horas_semana'); // Horas previstas por semana
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas rápidas
            $table->index('id_docente');
            $table->index('id_grupo');
            $table->index('id_periodo');
            $table->index('activo');

            // Única constraint: Un docente no puede estar asignado dos veces al mismo grupo en el mismo periodo
            $table->unique(['id_docente', 'id_grupo', 'id_periodo'], 'unique_docente_grupo_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carga_horarias');
    }
};
