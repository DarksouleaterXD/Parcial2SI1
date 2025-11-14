<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Traits\TracksChanges;

class Docente extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'docentes';

    protected $fillable = [
        'id_persona',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con Usuario (a través de Persona)
     */
    public function usuario()
    {
        return $this->hasOneThrough(
            User::class,      // Modelo final
            Persona::class,   // Modelo intermedio
            'id',             // Foreign key en Persona que relaciona con Docente
            'id',             // Foreign key en User que relaciona con Persona
            'id_persona',     // Local key en Docente
            'id_usuario'      // Local key en Persona
        );
    }

    /**
     * Relación con Persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id');
    }

    /**
     * Relación con Grupos (muchos a muchos)
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'grupo_docente',
            'id_docente',
            'id_grupo'
        );
    }

    /**
     * Relación con Horarios
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_docente', 'id');
    }

    /**
     * Relación con Asistencias
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'docente_id', 'id');
    }
}

