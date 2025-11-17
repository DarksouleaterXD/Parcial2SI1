<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
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

        // Verificar si es admin (columna rol O rol RBAC)
        if ($user->rol === 'admin' || $user->tieneRol('Super Administrador') || $user->tieneRol('Administrador')) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Acceso denegado. Se requieren permisos de administrador'
        ], 403);
    }
}
