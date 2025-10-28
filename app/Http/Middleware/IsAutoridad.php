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
        
        // Admin puede hacer todo
        if ($user->rol === 'admin') {
            return $next($request);
        }

        // Solo autoridad
        if ($user->rol !== 'autoridad') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere rol autoridad.'
            ], 403);
        }

        return $next($request);
    }
}
