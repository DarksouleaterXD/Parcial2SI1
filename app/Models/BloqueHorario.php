<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloqueHorario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bloques_horarios';

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'numero_bloque',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
    ];

    /**
     * Relación con Horarios
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_bloque', 'id');
    }
}
