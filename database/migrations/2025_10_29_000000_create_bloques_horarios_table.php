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
        // Crear tabla de bloques de horarios (franjas horarias predefinidas)
        Schema::create('bloques_horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Bloque 1, Bloque 2, etc.
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('numero_bloque'); // 1, 2, 3, etc.
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('activo');
            $table->unique('numero_bloque');
        });

        // Actualizar tabla horarios con referencias correctas
        Schema::table('horarios', function (Blueprint $table) {
            // Agregar columnas faltantes si no existen
            if (!Schema::hasColumn('horarios', 'id_grupo')) {
                $table->foreignId('id_grupo')->nullable()->constrained('grupos')->onDelete('cascade');
            }
            if (!Schema::hasColumn('horarios', 'id_docente')) {
                $table->foreignId('id_docente')->nullable()->constrained('docentes', 'id')->onDelete('cascade');
            }
            if (!Schema::hasColumn('horarios', 'id_bloque')) {
                $table->foreignId('id_bloque')->nullable()->constrained('bloques_horarios')->onDelete('cascade');
            }
            if (!Schema::hasColumn('horarios', 'dia_semana')) {
                $table->string('dia_semana', 20)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloques_horarios');
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['id_grupo_foreign']);
            $table->dropForeignKeyIfExists(['id_docente_foreign']);
            $table->dropForeignKeyIfExists(['id_bloque_foreign']);
            $table->dropColumnIfExists(['id_grupo', 'id_docente', 'id_bloque', 'dia_semana']);
        });
    }
};
