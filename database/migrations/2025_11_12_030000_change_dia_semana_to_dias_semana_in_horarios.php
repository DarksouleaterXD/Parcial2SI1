<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambiar dia_semana de string a JSON para soportar múltiples días
     */
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            // Cambiar dia_semana de string a JSON para almacenar array de días
            $table->json('dias_semana')->nullable()->after('id_bloque');
        });

        // Migrar datos existentes de dia_semana a dias_semana
        DB::statement("
            UPDATE horarios
            SET dias_semana = CASE
                WHEN dia_semana IS NOT NULL THEN json_build_array(dia_semana)
                ELSE NULL
            END
        ");

        // Eliminar columna antigua
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn('dia_semana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->string('dia_semana', 20)->nullable()->after('id_bloque');
        });

        // Restaurar datos (tomar el primer día del array)
        DB::statement("
            UPDATE horarios
            SET dia_semana = CASE
                WHEN dias_semana IS NOT NULL AND json_array_length(dias_semana) > 0
                THEN dias_semana->>0
                ELSE NULL
            END
        ");

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn('dias_semana');
        });
    }
};
