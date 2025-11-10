<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\TracksChanges;

class Politica extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'politicas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'condicion',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'condicion' => 'array',
    ];

    /**
     * Relación con el usuario que creó la política
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_politica', 'id_politica', 'id_rol')
            ->withTimestamps();
    }
}
