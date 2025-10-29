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
        Schema::table('carreras', function (Blueprint $table) {
            // Agregar columna sigla si no existe
            if (!Schema::hasColumn('carreras', 'sigla')) {
                $table->string('sigla', 10)->unique()->nullable()->after('codigo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            if (Schema::hasColumn('carreras', 'sigla')) {
                $table->dropColumn('sigla');
            }
        });
    }
};
