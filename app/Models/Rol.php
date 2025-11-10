<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\TracksChanges;

class Rol extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_sistema',
        'activo',
    ];

    protected $casts = [
        'es_sistema' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Relación con permisos
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'id_rol', 'id_permiso')
            ->withTimestamps();
    }

    /**
     * Relación con usuarios
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_rol', 'id_rol', 'id_usuario')
            ->withPivot('asignado_en', 'asignado_por')
            ->withTimestamps();
    }

    /**
     * Relación con políticas
     */
    public function politicas(): BelongsToMany
    {
        return $this->belongsToMany(Politica::class, 'rol_politica', 'id_rol', 'id_politica')
            ->withTimestamps();
    }

    /**
     * Verificar si el rol tiene un permiso específico
     */
    public function tienePermiso(string $nombrePermiso): bool
    {
        return $this->permisos()->where('nombre', $nombrePermiso)->exists();
    }

    /**
     * Verificar si el rol tiene permiso para una acción en un módulo
     */
    public function puedeEn(string $modulo, string $accion): bool
    {
        $nombrePermiso = strtolower($modulo) . '.' . strtolower($accion);
        return $this->tienePermiso($nombrePermiso);
    }
}
