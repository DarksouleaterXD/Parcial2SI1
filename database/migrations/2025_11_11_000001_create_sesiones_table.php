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
        Schema::create('sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->foreignId('aula_id')->constrained('aulas')->onDelete('cascade');
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->date('fecha'); // Fecha de la sesión
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('estado')->default('programada'); // programada, en_curso, finalizada, cancelada
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);

            // Ventana de marcado de asistencia
            $table->time('ventana_inicio')->nullable(); // ej: 15 min antes
            $table->time('ventana_fin')->nullable(); // ej: 30 min después del inicio

            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas rápidas
            $table->index('fecha');
            $table->index(['docente_id', 'fecha']);
            $table->index(['grupo_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones');
    }
};
