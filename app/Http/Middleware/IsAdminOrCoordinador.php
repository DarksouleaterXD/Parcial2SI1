<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdminOrCoordinador
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

        // Admin o Coordinador pueden acceder
        if ($user->rol === 'admin' || $user->rol === 'coordinador') {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'No autorizado. Se requiere rol de administrador o coordinador.'
        ], 403);
    }
}
