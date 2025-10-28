<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    use HasFactory;

    protected $table = 'periodos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'vigente',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
        'vigente' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un periodo tiene muchos grupos
     */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'id_periodo', 'id');
    }

    /**
     * Relación: Un periodo tiene muchas gestiones académicas
     */
    public function gestionesAcademicas(): HasMany
    {
        return $this->hasMany(GestionAcademica::class, 'id_periodo', 'id');
    }
}
