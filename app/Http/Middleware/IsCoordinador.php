<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsCoordinador
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

        // Admin puede hacer todo
        if ($user->rol === 'admin') {
            return $next($request);
        }

        // Solo coordinador
        if ($user->rol !== 'coordinador') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere rol coordinador.'
            ], 403);
        }

        return $next($request);
    }
}
