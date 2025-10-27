<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestionAcademica extends Model
{
    use HasFactory;

    protected $table = 'gestion_academica';

    protected $fillable = [
        'id_carrera',
        'ano',
        'periodo',
        'fecha_ini',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_ini' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación con Carrera
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id');
    }
}
