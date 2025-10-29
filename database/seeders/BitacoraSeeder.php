<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bitacora;
use App\Models\User;

class BitacoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * CÓMO USAR LA BITÁCORA:
     *
     * 1. MÉTODO MANUAL (en controladores):
     *    ===================================
     *    use App\Models\Bitacora;
     *    use App\Http\Controllers\BitacoraController;
     *
     *    // En tu controlador, después de crear/actualizar/eliminar:
     *    BitacoraController::registrar(
     *        Auth::id(),
     *        'carreras',
     *        'CREATE',
     *        $carrera->id,
     *        "Carrera '{$carrera->nombre}' creada"
     *    );
     *
     *
     * 2. MÉTODO AUTOMÁTICO (con Trait TracksChanges):
     *    ===============================================
     *    // En tu modelo:
     *    use App\Traits\TracksChanges;
     *
     *    class Carrera extends Model {
     *        use HasFactory, TracksChanges;
     *        ...
     *    }
     *
     *    // Ahora cualquier create/update/delete registrará automáticamente
     *    $carrera = Carrera::create([...]);  // CREATE logged
     *    $carrera->update([...]);             // UPDATE logged
     *    $carrera->delete();                  // DELETE logged
     *
     *
     * 3. ENDPOINTS DE LA BITÁCORA:
     *    ==========================
     *
     *    // Ver todas las bitácoras (con paginación)
     *    GET /api/bitacoras?page=1&per_page=20
     *
     *    // Filtrar por tabla
     *    GET /api/bitacoras?tabla=carreras
     *
     *    // Filtrar por operación
     *    GET /api/bitacoras?operacion=CREATE
     *
     *    // Filtrar por usuario
     *    GET /api/bitacoras?id_usuario=1
     *
     *    // Rango de fechas
     *    GET /api/bitacoras?fecha_inicio=2024-01-01&fecha_fin=2024-01-31
     *
     *    // Búsqueda por descripción
     *    GET /api/bitacoras?search=usuario
     *
     *    // Ver un registro específico
     *    GET /api/bitacoras/{id}
     *
     *    // Estadísticas
     *    GET /api/bitacoras/estadisticas/resumen
     *
     *    // Bitácoras por tabla específica
     *    GET /api/bitacoras/tabla/{tabla}?id_registro=5
     *
     *    // Bitácoras por usuario específico
     *    GET /api/bitacoras/usuario/{id_usuario}
     *
     *    // Exportar a CSV
     *    GET /api/bitacoras/exportar/csv?tabla=carreras
     *
     *
     * 4. PERMISOS POR ROL:
     *    ===================
     *    Admin:        Ver todo, filtrar todo
     *    Coordinador:  Ver todo, filtrar todo
     *    Autoridad:    Ver todo (solo lectura), filtrar todo
     *    Docente:      No tiene acceso
     *
     *
     * 5. ESTRUCTURA DE UN REGISTRO:
     *    ===========================
     *    {
     *        "id": 1,
     *        "id_usuario": 5,
     *        "usuario": {
     *            "id": 5,
     *            "nombre": "Carlos Admin",
     *            "email": "admin@ficct.com"
     *        },
     *        "tabla": "carreras",
     *        "operacion": "CREATE",
     *        "id_registro": 12,
     *        "descripcion": "Carrera 'Ingeniería en Sistemas' creada",
     *        "created_at": "2024-01-15T10:30:00.000000Z",
     *        "updated_at": "2024-01-15T10:30:00.000000Z"
     *    }
     */
    public function run(): void
    {
        // Crear bitácoras de ejemplo
        if (User::where('email', 'admin@ficct.com')->exists()) {
            $admin = User::where('email', 'admin@ficct.com')->first();

            Bitacora::create([
                'id_usuario' => $admin->id,
                'tabla' => 'carreras',
                'operacion' => 'CREATE',
                'id_registro' => 1,
                'descripcion' => 'Carrera "Ingeniería en Sistemas" creada por seeder',
            ]);

            Bitacora::create([
                'id_usuario' => $admin->id,
                'tabla' => 'usuarios',
                'operacion' => 'UPDATE',
                'id_registro' => $admin->id,
                'descripcion' => 'Perfil actualizado: email modificado',
            ]);

            Bitacora::create([
                'id_usuario' => $admin->id,
                'tabla' => 'periodos',
                'operacion' => 'CREATE',
                'id_registro' => 1,
                'descripcion' => 'Período "2024-1" creado',
            ]);
        }
    }
}
