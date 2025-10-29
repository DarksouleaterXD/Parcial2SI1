<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\TracksChanges;

class Carrera extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'carreras';

    protected $fillable = [
        'nombre',
        'codigo',
        'plan',
        'version',
    ];

    /**
     * Relación con Materias (muchos a muchos a través de MateriaCarrera)
     */
    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(
            Materia::class,
            'materia_carrera',
            'id_carrera',
            'id_materia'
        )->withPivot('plan', 'semestre', 'tipo', 'carga_teo', 'carga_pra');
    }

    /**
     * Relación con GestionAcademica
     */
    public function gestiones(): HasMany
    {
        return $this->hasMany(GestionAcademica::class, 'id_carrera', 'id');
    }
}
