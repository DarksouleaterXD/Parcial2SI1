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
            // Drop sigla column if it exists
            if (Schema::hasColumn('carreras', 'sigla')) {
                $table->dropColumn('sigla');
            }

            // Add plan and version columns
            $table->string('plan', 255)->nullable()->after('codigo');
            $table->string('version', 50)->nullable()->after('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            // Add sigla column back
            $table->string('sigla', 10)->nullable()->after('codigo');

            // Drop plan and version columns
            if (Schema::hasColumn('carreras', 'plan')) {
                $table->dropColumn('plan');
            }
            if (Schema::hasColumn('carreras', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
