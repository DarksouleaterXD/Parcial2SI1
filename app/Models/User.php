<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

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
}
