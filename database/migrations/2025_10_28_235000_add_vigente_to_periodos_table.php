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
        Schema::table('periodos', function (Blueprint $table) {
            if (!Schema::hasColumn('periodos', 'vigente')) {
                $table->boolean('vigente')->default(false)->after('activo');
                $table->index('vigente');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periodos', function (Blueprint $table) {
            if (Schema::hasColumn('periodos', 'vigente')) {
                $table->dropColumn('vigente');
            }
        });
    }
};
