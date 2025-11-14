<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksChanges;
use Carbon\Carbon;

class Sesion extends Model
{
    use HasFactory, SoftDeletes, TracksChanges;

    protected $table = 'sesiones';

    protected $fillable = [
        'horario_id',
        'docente_id',
        'aula_id',
        'grupo_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'observaciones',
        'ventana_inicio',
        'ventana_fin',
        'activo'
    ];

    protected $casts = [
        'fecha' => 'date',
        'activo' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function aula()
    {
        return $this->belongsTo(Aulas::class, 'aula_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'sesion_id');
    }

    // Scopes
    public function scopeHoy($query)
    {
        return $query->where('fecha', Carbon::today());
    }

    public function scopeDocente($query, $docenteId)
    {
        return $query->where('docente_id', $docenteId);
    }

    public function scopeProgramadas($query)
    {
        return $query->where('estado', 'programada');
    }

    public function scopeVigentes($query)
    {
        return $query->where('activo', true);
    }

    // Métodos de validación
    public function dentroDeVentana()
    {
        if (!$this->ventana_inicio || !$this->ventana_fin) {
            return false;
        }

        $ahora = Carbon::now()->format('H:i:s');
        return $ahora >= $this->ventana_inicio && $ahora <= $this->ventana_fin;
    }

    public function calcularVentanaMarcado()
    {
        // Ventana: 30 min antes hasta 20 min después del inicio de clase
        $inicio = Carbon::parse($this->hora_inicio);
        $this->ventana_inicio = $inicio->copy()->subMinutes(30)->format('H:i:s');
        $this->ventana_fin = $inicio->copy()->addMinutes(20)->format('H:i:s');
        return $this;
    }

    public function esDelDocente($docenteId)
    {
        return $this->docente_id == $docenteId;
    }

    public function tieneAsistenciaRegistrada()
    {
        return $this->asistencias()->exists();
    }
}
