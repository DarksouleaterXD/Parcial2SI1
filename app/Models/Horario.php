<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksChanges;

class Horario extends Model
{
    use HasFactory, SoftDeletes, TracksChanges;

    protected $table = 'horarios';

    protected $fillable = [
        'id_grupo',
        'id_aula',
        'id_docente',
        'id_bloque',
        'hora_inicio',
        'hora_fin',
        'dia_semana',
        'activo',
        'descripcion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
    ];

    /**
     * Relación con Grupo
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id');
    }

    /**
     * Relación con Aula
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aulas::class, 'id_aula', 'id');
    }

    /**
     * Relación con Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id');
    }

    /**
     * Relación con BloqueHorario
     */
    public function bloque(): BelongsTo
    {
        return $this->belongsTo(BloqueHorario::class, 'id_bloque', 'id');
    }

    /**
     * Relación con Asistencias
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'id_horario', 'id');
    }
}
