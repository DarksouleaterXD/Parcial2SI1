<?php

namespace App\Traits;

use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Support\Facades\Auth;

trait TracksChanges
{
    /**
     * Boot el trait para registrar cambios
     */
    public static function bootTracksChanges()
    {
        static::created(function ($model) {
            self::registrarCreacion($model);
        });

        static::updated(function ($model) {
            self::registrarActualizacion($model);
        });

        static::deleted(function ($model) {
            self::registrarEliminacion($model);
        });
    }

    /**
     * Registrar creación de modelo
     */
    private static function registrarCreacion($model)
    {
        if (!Auth::check()) {
            return;
        }

        $tabla = $model->getTable();
        $id = $model->getKey();

        Bitacora::create([
            'id_usuario' => Auth::id(),
            'ip_address' => IpHelper::getClientIp(),
            'tabla' => $tabla,
            'operacion' => 'crear',
            'id_registro' => $id,
            'descripcion' => "Nuevo registro creado en tabla: $tabla (ID: $id)",
        ]);
    }

    /**
     * Registrar actualización de modelo
     */
    private static function registrarActualizacion($model)
    {
        if (!Auth::check()) {
            return;
        }

        $tabla = $model->getTable();
        $id = $model->getKey();
        $cambios = $model->getChanges();

        $camposModificados = implode(', ', array_keys($cambios));
        $descripcion = "Registro actualizado en tabla: $tabla (ID: $id). Campos modificados: $camposModificados";

        Bitacora::create([
            'id_usuario' => Auth::id(),
            'ip_address' => IpHelper::getClientIp(),
            'tabla' => $tabla,
            'operacion' => 'editar',
            'id_registro' => $id,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Registrar eliminación de modelo
     */
    private static function registrarEliminacion($model)
    {
        if (!Auth::check()) {
            return;
        }

        $tabla = $model->getTable();
        $id = $model->getKey();

        Bitacora::create([
            'id_usuario' => Auth::id(),
            'ip_address' => IpHelper::getClientIp(),
            'tabla' => $tabla,
            'operacion' => 'eliminar',
            'id_registro' => $id,
            'descripcion' => "Registro eliminado de tabla: $tabla (ID: $id)",
        ]);
    }
}
