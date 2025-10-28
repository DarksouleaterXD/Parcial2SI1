<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('correo')->unique();
            $table->string('ci')->unique();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('lock_until')->nullable();
            $table->string('username')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
