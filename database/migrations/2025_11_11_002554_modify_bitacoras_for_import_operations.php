<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bitacoras', function (Blueprint $table) {
            // Hacer id_registro nullable para operaciones masivas
            $table->unsignedBigInteger('id_registro')->nullable()->change();
        });

        // Modificar el enum de operacion para incluir nuevas operaciones
        DB::statement("ALTER TABLE bitacoras DROP CONSTRAINT bitacoras_operacion_check");
        DB::statement("ALTER TABLE bitacoras ADD CONSTRAINT bitacoras_operacion_check CHECK (operacion IN ('crear', 'editar', 'eliminar', 'cambiar_estado', 'importacion_masiva', 'exportacion'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitacoras', function (Blueprint $table) {
            // Volver id_registro a NOT NULL
            $table->unsignedBigInteger('id_registro')->nullable(false)->change();
        });

        // Revertir el enum a los valores originales
        DB::statement("ALTER TABLE bitacoras DROP CONSTRAINT bitacoras_operacion_check");
        DB::statement("ALTER TABLE bitacoras ADD CONSTRAINT bitacoras_operacion_check CHECK (operacion IN ('crear', 'editar', 'eliminar', 'cambiar_estado'))");
    }
};
