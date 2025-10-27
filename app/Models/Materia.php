<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = [
        'codigo',
        'nombre',
        'creditos',
    ];

    /**
     * Relación con Carreras (muchos a muchos)
     */
    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(
            Carrera::class,
            'materia_carrera',
            'id_materia',
            'id_carrera'
        )->withPivot('plan', 'semestre', 'tipo', 'carga_teo', 'carga_pra');
    }

    /**
     * Relación con Grupos
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'grupo_materia',
            'id_materia',
            'id_grupo'
        );
    }
}
