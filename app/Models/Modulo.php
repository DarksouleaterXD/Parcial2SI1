<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TracksChanges;

class Modulo extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'modulos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Relación con permisos
     */
    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class, 'id_modulo');
    }
}
