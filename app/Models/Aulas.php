<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Aulas extends Model
{
    use HasFactory;

    protected $table = 'aulas';

    protected $fillable = [
        'numero',
        'tipo',
        'capacidad',
        'piso',
        'estado',
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
     * Relación con Horarios
     */
    public function horarios()
    {
        return $this->hasMany(Horario::class, 'id_aula', 'id');
    }
}
