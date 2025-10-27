<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';

    protected $fillable = [
        'id_usuario',
        'nombre',
        'apellido',
        'correo',
        'ci',
        'failed_login_attempts',
        'lock_until',
        'username',
    ];

    protected $casts = [
        'lock_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    /**
     * Relación con Bitacora
     */
    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'id_persona', 'id');
    }

    /**
     * Relación con Docente (si es docente)
     */
    public function docente()
    {
        return $this->hasOne(Docente::class, 'id_persona', 'id');
    }
}
