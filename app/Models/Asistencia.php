<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TracksChanges;

class Asistencia extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'asistencias';

    protected $fillable = [
        'id_horario',
        'id_docente',
        'fecha',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Relación con Horario
     */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'id_horario', 'id');
    }

    /**
     * Relación con Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id');
    }
}

