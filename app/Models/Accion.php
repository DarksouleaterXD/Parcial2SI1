<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TracksChanges;

class Accion extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'acciones';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Relación con permisos
     */
    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class, 'id_accion');
    }
}
