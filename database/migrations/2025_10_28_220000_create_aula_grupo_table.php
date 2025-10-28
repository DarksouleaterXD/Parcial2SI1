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
        Schema::create('aula_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_aula')->constrained('aulas')->onDelete('cascade');
            $table->foreignId('id_grupo')->constrained('grupos')->onDelete('cascade');
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['id_aula', 'id_grupo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aula_grupo');
    }
};
