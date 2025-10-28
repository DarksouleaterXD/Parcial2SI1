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
        Schema::table('aulas', function (Blueprint $table) {
            // Agregar columnas que faltan
            if (!Schema::hasColumn('aulas', 'tipo')) {
                $table->enum('tipo', ['teorica', 'practica', 'laboratorio', 'mixta'])->default('teorica')->after('nombre');
            }
            if (!Schema::hasColumn('aulas', 'piso')) {
                $table->integer('piso')->nullable()->after('ubicacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aulas', function (Blueprint $table) {
            if (Schema::hasColumn('aulas', 'tipo')) {
                $table->dropColumn('tipo');
            }
            if (Schema::hasColumn('aulas', 'piso')) {
                $table->dropColumn('piso');
            }
        });
    }
};
