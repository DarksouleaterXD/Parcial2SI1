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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones')->onDelete('cascade');
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->enum('estado', ['presente', 'ausente', 'retardo', 'justificado'])->default('ausente');
            $table->enum('metodo_registro', ['formulario', 'qr', 'manual'])->default('formulario');

            // Timestamps de marcado
            $table->timestamp('marcado_at')->nullable(); // Hora exacta de marcado
            $table->time('hora_marcado')->nullable(); // Solo la hora para validaciones

            // Información adicional
            $table->text('observacion')->nullable();
            $table->string('evidencia_url')->nullable(); // Ruta a archivo adjunto
            $table->string('ip_marcado', 45)->nullable();
            $table->string('geolocalizacion')->nullable(); // Coordenadas GPS en formato "lat,lng"

            // Validación del coordinador
            $table->boolean('validado')->default(false);
            $table->foreignId('validado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamp('validado_at')->nullable();
            $table->text('observacion_validacion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('marcado_at');
            $table->index(['docente_id', 'marcado_at']);
            $table->index('validado');

            // Constraint: Una asistencia por sesión por docente
            $table->unique(['sesion_id', 'docente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
