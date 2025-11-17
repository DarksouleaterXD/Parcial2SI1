<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAutoridad
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
        } catch (\Exception $e) {
            // Si falla la verificación RBAC, continuar
        }

        // Verificar si es autoridad (columna rol)
        if ($user->rol === 'autoridad') {
            return $next($request);
        }

        // Verificar si tiene rol RBAC de autoridad
        try {
            if ($user->roles()->where('nombre', 'Autoridad')->exists()) {
                return $next($request);
            }
        } catch (\Exception $e) {
            // Si falla la verificación RBAC, continuar
        }

        return response()->json([
            'success' => false,
            'message' => 'No autorizado. Se requiere rol autoridad.'
        ], 403);
    }
}
