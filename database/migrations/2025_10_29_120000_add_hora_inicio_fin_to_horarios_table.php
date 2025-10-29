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
        Schema::table('horarios', function (Blueprint $table) {
            // Hacer id_bloque nullable
            $table->unsignedBigInteger('id_bloque')->nullable()->change();

            // Agregar campos de hora_inicio y hora_fin
            $table->time('hora_inicio')->nullable()->after('id_bloque');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            // Revertir id_bloque a requerido
            $table->unsignedBigInteger('id_bloque')->nullable(false)->change();

            // Eliminar campos
            $table->dropColumn(['hora_inicio', 'hora_fin']);
        });
    }
};
