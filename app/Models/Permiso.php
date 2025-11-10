<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\TracksChanges;

class Permiso extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'permisos';

    protected $fillable = [
        'id_modulo',
        'id_accion',
        'nombre',
        'descripcion',
    ];

    /**
     * Relación con módulo
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }

    /**
     * Relación con acción
     */
    public function accion(): BelongsTo
    {
        return $this->belongsTo(Accion::class, 'id_accion');
    }

    /**
     * Relación con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'id_permiso', 'id_rol')
            ->withTimestamps();
    }
}
