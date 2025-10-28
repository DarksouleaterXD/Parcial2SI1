<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsDocente
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

        // Solo docente
        if ($user->rol !== 'docente') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere rol docente.'
            ], 403);
        }

        return $next($request);
    }
}
