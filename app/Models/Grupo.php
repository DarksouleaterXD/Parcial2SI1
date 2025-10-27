<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'nombre',
        'paralelo',
        'turno',
        'capacidad',
    ];

    /**
     * Relación con Materias (muchos a muchos)
     */
    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(
            Materia::class,
            'grupo_materia',
            'id_grupo',
            'id_materia'
        );
    }

    /**
     * Relación con Docentes (muchos a muchos)
     */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'grupo_docente',
            'id_grupo',
            'id_docente'
        );
    }

    /**
     * Relación con Horarios
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_grupo', 'id');
    }

    /**
     * Relación con Aulas
     */
    public function aulas(): BelongsToMany
    {
        return $this->belongsToMany(
            Aulas::class,
            'aula_grupo',
            'id_grupo',
            'id_aula'
        );
    }
}
