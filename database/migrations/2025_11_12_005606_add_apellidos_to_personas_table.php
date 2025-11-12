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
        Schema::table('personas', function (Blueprint $table) {
            // Agregar apellido_paterno y apellido_materno después del nombre
            $table->string('apellido_paterno')->nullable()->after('nombre');
            $table->string('apellido_materno')->nullable()->after('apellido_paterno');

            // Agregar telefono si no existe
            if (!Schema::hasColumn('personas', 'telefono')) {
                $table->string('telefono', 15)->nullable()->after('correo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn(['apellido_paterno', 'apellido_materno']);
            if (Schema::hasColumn('personas', 'telefono')) {
                $table->dropColumn('telefono');
            }
        });
    }
};
