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
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropIndex(['turno']); // Eliminar índice primero
            $table->dropColumn('turno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->enum('turno', ['mañana', 'tarde', 'noche'])->default('mañana')->after('paralelo');
            $table->index('turno');
        });
    }
};
