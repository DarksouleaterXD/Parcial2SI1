<?php

namespace App\Helpers;

class IpHelper
{
    /**
     * Obtiene la IP del cliente de manera confiable
     */
    public static function getClientIp()
    {
        $ip = null;

        // Verificar si la IP viene a través de un proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Si no hay IP o es inválida, usar la de Laravel
        if (empty($ip)) {
            $ip = request()->ip() ?? '127.0.0.1';
        }

        return $ip;
    }
}
