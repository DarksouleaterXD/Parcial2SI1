<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\TracksChanges;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, TracksChanges;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
        'activo',
        'id_persona',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Verificar si es superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /**
     * Verificar si es coordinador
     */
    public function isCoordinador(): bool
    {
        return $this->rol === 'coordinador';
    }

    /**
     * Verificar si es autoridad
     */
    public function isAutoridad(): bool
    {
        return $this->rol === 'autoridad';
    }

    /**
     * Verificar si es docente
     */
    public function isDocente(): bool
    {
        return $this->rol === 'docente';
    }

    /**
     * Relación con Persona
     */
    public function persona(): HasOne
    {
        return $this->hasOne(Persona::class, 'id', 'id_persona');
    }

    /**
     * Relación con Docente (si es docente)
     */
    public function docente()
    {
        return $this->hasOne(Docente::class, 'id_persona', 'id_persona');
    }

    /**
     * Relación con roles RBAC
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'id_usuario', 'id_rol')
            ->withPivot('asignado_en', 'asignado_por')
            ->withTimestamps();
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol(string $nombreRol): bool
    {
        return $this->roles()->where('nombre', $nombreRol)->exists();
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function tienePermiso(string $nombrePermiso): bool
    {
        foreach ($this->roles as $rol) {
            if ($rol->tienePermiso($nombrePermiso)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si el usuario puede realizar una acción en un módulo
     */
    public function puedeEn(string $modulo, string $accion): bool
    {
        foreach ($this->roles as $rol) {
            if ($rol->puedeEn($modulo, $accion)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener todos los permisos del usuario (de todos sus roles)
     */
    public function obtenerPermisos()
    {
        $permisos = collect();
        foreach ($this->roles as $rol) {
            $permisos = $permisos->merge($rol->permisos);
        }
        return $permisos->unique('id');
    }
}
