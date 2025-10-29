<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TracksChanges;

/**
 * EJEMPLO DE CÓMO USAR EL TRAIT TracksChanges
 *
 * Agregue el trait a cualquier modelo que quiera rastrear cambios:
 *
 * use App\Traits\TracksChanges;
 *
 * class MiModelo extends Model {
 *     use HasFactory, TracksChanges;
 *     ...
 * }
 *
 * Automáticamente se registrarán:
 * - CREATE cuando se crea un nuevo registro
 * - UPDATE cuando se actualiza un registro
 * - DELETE cuando se elimina un registro
 *
 * Todos con el usuario autenticado, tabla, ID del registro y descripción detallada.
 */

class EjemploUsoTracksChanges extends Model
{
    use HasFactory, TracksChanges;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Al crear: Bitacora registrará automaticamente
     * Bitacora::create([
     *     'id_usuario' => Auth::id(),
     *     'tabla' => 'ejemplo_uso_tracks_changes',
     *     'operacion' => 'CREATE',
     *     'id_registro' => $this->id,
     *     'descripcion' => 'Nuevo registro creado...'
     * ])
     *
     * $modelo = EjemploUsoTracksChanges::create([
     *     'nombre' => 'Test',
     *     'descripcion' => 'Descripción'
     * ]);
     */

    /**
     * Al actualizar: Bitacora registrará automaticamente
     * Bitacora::create([
     *     'id_usuario' => Auth::id(),
     *     'tabla' => 'ejemplo_uso_tracks_changes',
     *     'operacion' => 'UPDATE',
     *     'id_registro' => $this->id,
     *     'descripcion' => 'Registro actualizado... Campos modificados: nombre'
     * ])
     *
     * $modelo->update(['nombre' => 'Nuevo nombre']);
     */

    /**
     * Al eliminar: Bitacora registrará automaticamente
     * Bitacora::create([
     *     'id_usuario' => Auth::id(),
     *     'tabla' => 'ejemplo_uso_tracks_changes',
     *     'operacion' => 'DELETE',
     *     'id_registro' => $this->id,
     *     'descripcion' => 'Registro eliminado...'
     * ])
     *
     * $modelo->delete();
     */
}
