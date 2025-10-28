<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Agregar email si no existe
            if (!Schema::hasColumn('usuarios', 'email')) {
                $table->string('email')->unique()->nullable();
            }
            // Agregar nombre si no existe
            if (!Schema::hasColumn('usuarios', 'nombre')) {
                $table->string('nombre')->nullable();
            }
            // Agregar id_persona si no existe
            if (!Schema::hasColumn('usuarios', 'id_persona')) {
                $table->unsignedBigInteger('id_persona')->nullable();
            }
            // Agregar activo si no existe
            if (!Schema::hasColumn('usuarios', 'activo')) {
                $table->boolean('activo')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
