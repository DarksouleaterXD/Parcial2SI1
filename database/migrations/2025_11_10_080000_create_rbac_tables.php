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
        // Tabla de módulos del sistema
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique(); // ej: aulas, materias, grupos
            $table->string('descripcion', 255)->nullable();
            $table->string('icono', 50)->nullable(); // Para UI
            $table->integer('orden')->default(0); // Orden en menú
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Tabla de acciones disponibles por módulo
        Schema::create('acciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50); // ej: crear, editar, eliminar, ver, exportar
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // Tabla de permisos (módulo + acción)
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_modulo')->constrained('modulos')->onDelete('cascade');
            $table->foreignId('id_accion')->constrained('acciones')->onDelete('cascade');
            $table->string('nombre', 100)->unique(); // ej: aulas.crear, materias.editar
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->unique(['id_modulo', 'id_accion']);
        });

        // Tabla de roles mejorada
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('es_sistema')->default(false); // Roles del sistema no se pueden eliminar
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Tabla pivot: rol - permiso
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('roles')->onDelete('cascade');
            $table->foreignId('id_permiso')->constrained('permisos')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['id_rol', 'id_permiso']);
        });

        // Tabla pivot: usuario - rol (un usuario puede tener múltiples roles)
        Schema::create('usuario_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('id_rol')->constrained('roles')->onDelete('cascade');
            $table->timestamp('asignado_en')->useCurrent();
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();

            $table->unique(['id_usuario', 'id_rol']);
        });

        // Tabla de políticas (reglas adicionales)
        Schema::create('politicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->text('condicion')->nullable(); // JSON con reglas
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();
        });

        // Tabla pivot: rol - política
        Schema::create('rol_politica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('roles')->onDelete('cascade');
            $table->foreignId('id_politica')->constrained('politicas')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['id_rol', 'id_politica']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_politica');
        Schema::dropIfExists('politicas');
        Schema::dropIfExists('usuario_rol');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('acciones');
        Schema::dropIfExists('modulos');
    }
};
