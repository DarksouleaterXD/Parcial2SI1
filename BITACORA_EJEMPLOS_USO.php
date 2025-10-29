<?php

/**
 * PRUEBA RÁPIDA DEL SISTEMA DE BITÁCORA
 *
 * Ejecutar desde terminal:
 * cd c:\backend2ex\backend
 * php artisan tinker
 *
 * Luego copiar y pegar los comandos abajo:
 */

// 1. Ver todas las bitácoras
// Bitacora::all();

// 2. Ver últimas 10 bitácoras ordenadas por fecha
// Bitacora::orderBy('created_at', 'desc')->limit(10)->get();

// 3. Ver bitácoras de un usuario específico
// Bitacora::where('id_usuario', 1)->get();

// 4. Ver bitácoras por tabla
// Bitacora::where('tabla', 'carreras')->get();

// 5. Ver bitácoras por operación
// Bitacora::where('operacion', 'CREATE')->get();

// 6. Registrar una bitácora manual
// App\Http\Controllers\BitacoraController::registrar(
//     1,  // id_usuario
//     'test_table',  // tabla
//     'CREATE',  // operación
//     999,  // id_registro
//     'Test de bitácora'  // descripción
// );

// 7. Contar bitácoras por tabla
// Bitacora::groupBy('tabla')->selectRaw('tabla, COUNT(*) as total')->get();

// 8. Contar bitácoras por operación
// Bitacora::groupBy('operacion')->selectRaw('operacion, COUNT(*) as total')->get();

// 9. Ver cambios de un registro específico
// Bitacora::where('tabla', 'carreras')->where('id_registro', 1)->get();

// 10. Borrar bitácoras antiguas (más de 30 días)
// Bitacora::where('created_at', '<', now()->subDays(30))->delete();

// ============================================
// PRUEBA DE ENDPOINTS DESDE INSOMNIA/POSTMAN
// ============================================

/*
1. Obtener token de admin:
   POST http://localhost:8000/api/login
   {
     "email": "admin@ficct.com",
     "password": "12345678"
   }

2. Ver todas las bitácoras:
   GET http://localhost:8000/api/bitacoras
   Headers: Authorization: Bearer {token}

3. Filtrar por tabla:
   GET http://localhost:8000/api/bitacoras?tabla=usuarios
   Headers: Authorization: Bearer {token}

4. Ver estadísticas:
   GET http://localhost:8000/api/bitacoras/estadisticas/resumen
   Headers: Authorization: Bearer {token}

5. Exportar CSV:
   GET http://localhost:8000/api/bitacoras/exportar/csv
   Headers: Authorization: Bearer {token}

6. Ver bitácoras de una tabla específica:
   GET http://localhost:8000/api/bitacoras/tabla/usuarios
   Headers: Authorization: Bearer {token}

7. Ver bitácoras de un usuario:
   GET http://localhost:8000/api/bitacoras/usuario/1
   Headers: Authorization: Bearer {token}

8. Ver un registro específico:
   GET http://localhost:8000/api/bitacoras/5
   Headers: Authorization: Bearer {token}
*/

// ============================================
// CÓMO INTEGRAR EN CONTROLADORES EXISTENTES
// ============================================

/*
// En CarreraController.php:

use App\Http\Controllers\BitacoraController;

// En store():
public function store(Request $request)
{
    $carrera = Carrera::create($request->validated());

    // Registrar en bitácora
    BitacoraController::registrar(
        Auth::id(),
        'carreras',
        'CREATE',
        $carrera->id,
        "Carrera '{$carrera->nombre}' ({$carrera->sigla}) creada"
    );

    return response()->json($carrera, 201);
}

// En update():
public function update(Request $request, Carrera $carrera)
{
    $oldData = $carrera->getAttributes();
    $carrera->update($request->validated());

    // Registrar cambios
    $cambios = [];
    foreach ($request->validated() as $key => $value) {
        if ($oldData[$key] !== $value) {
            $cambios[$key] = ['de' => $oldData[$key], 'a' => $value];
        }
    }

    BitacoraController::registrar(
        Auth::id(),
        'carreras',
        'UPDATE',
        $carrera->id,
        "Carrera '{$carrera->nombre}' actualizada. Cambios: " . json_encode($cambios)
    );

    return response()->json($carrera, 200);
}

// En destroy():
public function destroy(Carrera $carrera)
{
    $nombre = $carrera->nombre;
    $carrera->delete();

    // Registrar eliminación
    BitacoraController::registrar(
        Auth::id(),
        'carreras',
        'DELETE',
        $carrera->id,
        "Carrera '{$nombre}' eliminada"
    );

    return response()->json(null, 204);
}
*/
