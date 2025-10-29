<?php

namespace App\Listeners;

use App\Models\Bitacora;
use App\Helpers\IpHelper;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BitacoraListener
{
    /**
     * Registrar cambios en la bitácora cuando se crean modelos
     */
    public static function registrarCreacion($nombreTabla, $id, $datos)
    {
        if (!Auth::check()) {
            return;
        }

        $descripcion = "Nuevo registro creado en $nombreTabla";

        Bitacora::create([
            'id_usuario' => Auth::id(),
            'ip_address' => IpHelper::getClientIp(),
            'tabla' => $nombreTabla,
            'operacion' => 'crear',
            'id_registro' => $id,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Registrar cambios en la bitácora cuando se actualizan modelos
     */
    public static function registrarActualizacion($nombreTabla, $id, $cambios)
    {
        if (!Auth::check()) {
            return;
        }

        $descripcion = "Registro actualizado en $nombreTabla. Cambios: " . json_encode($cambios);

        Bitacora::create([
            'id_usuario' => Auth::id(),
            'ip_address' => IpHelper::getClientIp(),
            'tabla' => $nombreTabla,
            'operacion' => 'editar',
            'id_registro' => $id,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Registrar cambios en la bitácora cuando se eliminan modelos
     */
    public static function registrarEliminacion($nombreTabla, $id, $datos)
    {
        if (!Auth::check()) {
            return;
        }

        $descripcion = "Registro eliminado de $nombreTabla";

        Bitacora::create([
            'id_usuario' => Auth::id(),
            'ip_address' => IpHelper::getClientIp(),
            'tabla' => $nombreTabla,
            'operacion' => 'eliminar',
            'id_registro' => $id,
            'descripcion' => $descripcion,
        ]);
    }
}
