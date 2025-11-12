<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksChanges;

class Asistencia extends Model
{
    use HasFactory, SoftDeletes, TracksChanges;

    protected $table = 'asistencias';

    protected $fillable = [
        'sesion_id',
        'docente_id',
        'estado',
        'metodo_registro',
        'marcado_at',
        'hora_marcado',
        'observacion',
        'evidencia_url',
        'ip_marcado',
        'geolocalizacion',
        'validado',
        'validado_por',
        'validado_at',
        'observacion_validacion'
    ];

    protected $casts = [
        'marcado_at' => 'datetime',
        'validado_at' => 'datetime',
        'validado' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'sesion_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function validadoPor()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    // Scopes
    public function scopePendientesValidacion($query)
    {
        return $query->where('validado', false);
    }

    public function scopeValidadas($query)
    {
        return $query->where('validado', true);
    }

    public function scopeRetardos($query)
    {
        return $query->where('estado', 'retardo');
    }

    public function scopeAusencias($query)
    {
        return $query->where('estado', 'ausente');
    }

    // Métodos
    public function esRetardo()
    {
        if (!$this->hora_marcado || !$this->sesion) {
            return false;
        }

        return $this->hora_marcado > $this->sesion->hora_inicio;
    }

    public function calcularEstado()
    {
        if ($this->estado === 'ausente' || $this->estado === 'justificado') {
            return $this->estado;
        }

        return $this->esRetardo() ? 'retardo' : 'presente';
    }

    public function puedeSerEditada()
    {
        // Solo se puede editar si no ha sido validada
        return !$this->validado;
    }
}

