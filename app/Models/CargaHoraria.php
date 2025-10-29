<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargaHoraria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carga_horarias';

    protected $fillable = [
        'id_docente',
        'id_grupo',
        'id_periodo',
        'horas_semana',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'horas_semana' => 'integer',
    ];

    /**
     * Relación con Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id');
    }

    /**
     * Relación con Grupo
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id');
    }

    /**
     * Relación con Periodo
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'id_periodo', 'id');
    }
}
