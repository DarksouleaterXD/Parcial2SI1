<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aulas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aulas';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'capacidad',
        'ubicacion',
        'piso',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'capacidad' => 'integer',
        'piso' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con Grupos (muchos a muchos)
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            Grupo::class,
            'aula_grupo',
            'id_aula',
            'id_grupo'
        );
    }

    /**
     * Get all the bitacora entries for this aula.
     */
    public function bitacora()
    {
        return $this->hasMany(Bitacora::class, 'id_registro')
            ->where('tabla', 'aulas');
    }

    /**
     * Relación con Horarios
     */
    public function horarios()
    {
        return $this->hasMany(Horario::class, 'id_aula', 'id');
    }
}
