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
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios')->onDelete('cascade');
            $table->string('tabla')->comment('Nombre de la tabla modificada');
            $table->enum('operacion', ['crear', 'editar', 'eliminar', 'cambiar_estado'])->comment('Tipo de operación realizada');
            $table->unsignedBigInteger('id_registro')->comment('ID del registro en la tabla');
            $table->text('descripcion')->nullable()->comment('Descripción de los cambios');
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('id_usuario');
            $table->index('tabla');
            $table->index('operacion');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
