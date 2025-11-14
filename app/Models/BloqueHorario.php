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
    ];

    protected $appends = ['hora_inicio_formatted', 'hora_fin_formatted'];

    /**
     * Accessor para formatear hora_inicio
     */
    public function getHoraInicioFormattedAttribute()
    {
        return $this->hora_inicio ? date('H:i', strtotime($this->hora_inicio)) : null;
    }

    /**
     * Accessor para formatear hora_fin
     */
    public function getHoraFinFormattedAttribute()
    {
        return $this->hora_fin ? date('H:i', strtotime($this->hora_fin)) : null;
    }

    /**
     * Relación con Horarios
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_bloque', 'id');
    }
}
