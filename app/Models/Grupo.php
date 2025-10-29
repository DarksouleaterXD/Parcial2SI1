<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TracksChanges;

class Grupo extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'grupos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_materia',
        'id_periodo',
        'paralelo',
        'turno',
        'capacidad',
        'codigo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un grupo pertenece a una Materia
     */
    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id');
    }

    /**
     * Relación: Un grupo pertenece a un Periodo
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'id_periodo', 'id');
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
     * Relación con Horarios (uno a muchos)
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_grupo', 'id');
    }

    /**
     * Relación con Aulas (muchos a muchos)
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

    /**
     * Relación con Bitácora
     */
    public function bitacora(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'id_recurso', 'id')
            ->where('tipo_recurso', 'grupo');
    }
}
