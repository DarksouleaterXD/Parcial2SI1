<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Materia extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'carrera_id',
        'horas_semana',
        'activo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
        'horas_semana' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the carrera that owns the materia.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * Get all the bitacora entries for this materia.
     */
    public function bitacora()
    {
        return $this->hasMany(Bitacora::class, 'id_registro')
            ->where('tabla', 'materias');
    }
}
