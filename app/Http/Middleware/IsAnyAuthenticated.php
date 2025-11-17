<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para usuarios autenticados con roles administrativos o docente
 * Incluye: Admin, Coordinador, Autoridad, Docente
 */
class IsAnyAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        $user = auth()->user();

        // Verificar si es admin (columna rol)
        if ($user->rol === 'admin') {
            return $next($request);
        }

        // Verificar si tiene rol RBAC de administrador
        try {
            if ($user->roles()->whereIn('nombre', ['Super Administrador', 'Administrador'])->exists()) {
                return $next($request);
            }
        } catch (\Exception $e) {}

        // Verificar si es coordinador (columna rol)
        if ($user->rol === 'coordinador') {
            return $next($request);
        }

        // Verificar si tiene rol RBAC de coordinador
        try {
            if ($user->roles()->where('nombre', 'Coordinador')->exists()) {
                return $next($request);
            }
        } catch (\Exception $e) {}

        // Verificar si es autoridad (columna rol)
        if ($user->rol === 'autoridad') {
            return $next($request);
        }

        // Verificar si tiene rol RBAC de autoridad
        try {
            if ($user->roles()->where('nombre', 'Autoridad')->exists()) {
                return $next($request);
            }
        } catch (\Exception $e) {}

        // Verificar si es docente (columna rol)
        if ($user->rol === 'docente') {
            return $next($request);
        }

        // Verificar si tiene rol RBAC de docente
        try {
            if ($user->roles()->where('nombre', 'Docente')->exists()) {
                return $next($request);
            }
        } catch (\Exception $e) {}

        return response()->json([
            'success' => false,
            'message' => 'No autorizado. Se requiere un rol válido en el sistema.'
        ], 403);
    }
}
