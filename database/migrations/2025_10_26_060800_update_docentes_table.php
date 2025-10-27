<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migración ya no es necesaria - la tabla docentes se crea en la migración create_docentes_table
        // Se deja vacía para mantener el historial de migraciones
    }

    public function down(): void
    {
        // No hacer nada - esta es una migración vacía
    }
};
