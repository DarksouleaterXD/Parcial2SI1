<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\AulasController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\GestionAcademicaController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\BloqueHorarioController;
use App\Http\Controllers\CargaHorariaController;
use App\Http\Controllers\HorarioDocenteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RolPermisoController;

/**
 * RUTAS PÚBLICAS (sin autenticación)
 */
Route::post('/login', [AuthController::class, 'login']);

/**
 * RUTAS PROTEGIDAS (requieren autenticación)
 */
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación - Disponible para todos
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/perfil', [AuthController::class, 'perfil']);
    Route::post('/cambiar-contrasena', [AuthController::class, 'cambiarContrasena']);

    /**
     * RUTAS ADMIN (Superusuario - puede hacer TODO)
     * admin puede acceder a todas estas rutas
     */
    Route::middleware('IsAdmin')->group(function () {
        // Gestión de usuarios
        Route::get('/usuarios-lista', [AuthController::class, 'listarUsuarios']);
        Route::post('/registrar-usuario', [AuthController::class, 'registrarUsuario']);

        // CU3 - Gestionar Materias
        Route::apiResource('materias', MateriaController::class);
        Route::patch('materias/{materia}/estado', [MateriaController::class, 'updateEstado']);

        // CU2 - Gestionar Docentes
        Route::apiResource('docentes', DocenteController::class);
        Route::patch('/docentes/{docente}/estado', [DocenteController::class, 'updateEstado']);

        // CU4 - Gestionar Carreras
        Route::apiResource('carreras', CarreraController::class);
        Route::get('carreras-lista', [CarreraController::class, 'lista']);

        // CU6 - Gestionar Periodos Académicos
        Route::apiResource('periodos', PeriodoController::class);
        Route::patch('periodos/{periodo}/vigente', [PeriodoController::class, 'marcarVigente']);

        // CU5 - Gestionar Grupos
        Route::apiResource('grupos', GrupoController::class);

        // CU4 - Gestionar Aulas
        Route::apiResource('aulas', AulasController::class);
        Route::patch('aulas/{aula}/estado', [AulasController::class, 'updateEstado']);

        // CU9 - Consultar Disponibilidad de Aulas
        Route::get('aulas-disponibilidad', [AulasController::class, 'disponibilidad']);

        // CU7 - Gestionar Horarios
        Route::apiResource('bloques-horarios', BloqueHorarioController::class);
        Route::patch('bloques-horarios/{bloqueHorario}/estado', [BloqueHorarioController::class, 'updateEstado']);
        Route::apiResource('horarios', HorarioController::class);

        // CU7 - Gestionar Carga Horaria (Asignar docentes a grupos con horas/semana)
        Route::apiResource('carga-horaria', CargaHorariaController::class);
        Route::post('carga-horaria/por-grupo-periodo', [CargaHorariaController::class, 'porGrupoPeriodo']);
        Route::post('carga-horaria/por-docente-periodo', [CargaHorariaController::class, 'porDocentePeriodo']);

        // CU10 - Tablero Administrativo (Dashboard)
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos']);
        Route::get('dashboard/catalogos', [DashboardController::class, 'catalogos']);

        // CU20 - Bitácora (solo admin puede ver todo)
        // Rutas específicas primero (antes de {bitacora})
        Route::get('bitacoras/seed/datos-prueba', [BitacoraController::class, 'seedDatos']);
        Route::get('bitacoras/estadisticas/resumen', [BitacoraController::class, 'estadisticas']);
        Route::get('bitacoras/tabla/{tabla}', [BitacoraController::class, 'porTabla']);
        Route::get('bitacoras/usuario/{id_usuario}', [BitacoraController::class, 'porUsuario']);
        Route::get('bitacoras/exportar/csv', [BitacoraController::class, 'exportarCSV']);
        // Rutas genéricas al final
        Route::get('bitacoras', [BitacoraController::class, 'index']);
        Route::get('bitacoras/{bitacora}', [BitacoraController::class, 'show']);

        // CU6 - Gestión Académica
        Route::apiResource('gestion-academica', GestionAcademicaController::class);

        // Personas
        Route::apiResource('personas', PersonaController::class);
        Route::get('/personas/{persona}/bitacora', [PersonaController::class, 'bitacora']);

        // CU13 - Gestionar Roles y Permisos (RBAC)
        Route::apiResource('roles', RolPermisoController::class);
        Route::get('permisos', [RolPermisoController::class, 'listarPermisos']);
        Route::get('modulos', [RolPermisoController::class, 'listarModulos']);
        Route::get('acciones', [RolPermisoController::class, 'listarAcciones']);
        Route::post('usuarios/{userId}/roles', [RolPermisoController::class, 'asignarRolAUsuario']);
        Route::delete('usuarios/{userId}/roles/{rolId}', [RolPermisoController::class, 'removerRolDeUsuario']);

        // Gestión completa de usuarios (CRUD)
        Route::apiResource('usuarios', UserController::class);
    });

    /**
     * RUTAS COORDINADOR
     * Puede: CU2 (Docentes), CU3 (Materias), CU4 (Aulas), CU5 (Grupos), CU6 (Periodos-lectura), CU7 (Horarios), CU8-9 (Consultas), CU14-15 (Asignación)
     */
    Route::middleware('IsCoordinador')->group(function () {
        Route::apiResource('materias', MateriaController::class);
        Route::patch('materias/{materia}/estado', [MateriaController::class, 'updateEstado']);
        Route::apiResource('docentes', DocenteController::class);
        Route::patch('/docentes/{docente}/estado', [DocenteController::class, 'updateEstado']);
        Route::apiResource('aulas', AulasController::class);
        Route::patch('aulas/{aula}/estado', [AulasController::class, 'updateEstado']);
        Route::apiResource('bloques-horarios', BloqueHorarioController::class);
        Route::patch('bloques-horarios/{bloqueHorario}/estado', [BloqueHorarioController::class, 'updateEstado']);
        Route::apiResource('grupos', GrupoController::class);
        Route::apiResource('horarios', HorarioController::class);

        // CU7 - Gestionar Carga Horaria (Asignar docentes a grupos con horas/semana)
        Route::apiResource('carga-horaria', CargaHorariaController::class);
        Route::post('carga-horaria/por-grupo-periodo', [CargaHorariaController::class, 'porGrupoPeriodo']);
        Route::post('carga-horaria/por-docente-periodo', [CargaHorariaController::class, 'porDocentePeriodo']);

        // CU8 - Consultar Horario Semanal (Coordinador consulta horario de docentes)
        Route::get('docentes-horarios', [HorarioDocenteController::class, 'listadoDocentesHorarios']);
        Route::get('horario-docente/{id_docente}', [HorarioDocenteController::class, 'horarioDocente']);

        // CU9 - Consultar Disponibilidad de Aulas
        Route::get('aulas-disponibilidad', [AulasController::class, 'disponibilidad']);

        // CU10 - Tablero Administrativo (Dashboard)
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos']);
        Route::get('dashboard/catalogos', [DashboardController::class, 'catalogos']);

        // CU20 - Bitácora (Coordinador puede ver bitácora)
        Route::get('bitacoras/seed/datos-prueba', [BitacoraController::class, 'seedDatos']);
        Route::get('bitacoras/estadisticas/resumen', [BitacoraController::class, 'estadisticas']);
        Route::get('bitacoras/tabla/{tabla}', [BitacoraController::class, 'porTabla']);
        Route::get('bitacoras/usuario/{id_usuario}', [BitacoraController::class, 'porUsuario']);
        Route::get('bitacoras/exportar/csv', [BitacoraController::class, 'exportarCSV']);
        Route::get('bitacoras', [BitacoraController::class, 'index']);
        Route::get('bitacoras/{bitacora}', [BitacoraController::class, 'show']);

        // CU6 - Coordinador solo puede consultar periodos
        Route::get('periodos', [PeriodoController::class, 'index']);
        Route::get('periodos/{periodo}', [PeriodoController::class, 'show']);

        // CU3 - Coordinador puede consultar materias (necesario para crear grupos)
        Route::get('materias', [MateriaController::class, 'index']);
        Route::get('materias/{materia}', [MateriaController::class, 'show']);
    });

    /**
     * RUTAS AUTORIDAD
     * Puede: Consultar (lectura) horarios, aulas, disponibilidad, ver reportes, bitácora
     */
    Route::middleware('IsAutoridad')->group(function () {
        // Solo lectura para autoridad
        Route::get('horarios', [HorarioController::class, 'index']);
        Route::get('horarios/{horario}', [HorarioController::class, 'show']);
        Route::get('aulas', [AulasController::class, 'index']);
        Route::get('aulas/{aula}', [AulasController::class, 'show']);
        Route::get('bitacoras/seed/datos-prueba', [BitacoraController::class, 'seedDatos']);
        Route::get('bitacoras/estadisticas/resumen', [BitacoraController::class, 'estadisticas']);
        Route::get('bitacoras/tabla/{tabla}', [BitacoraController::class, 'porTabla']);
        Route::get('bitacoras/usuario/{id_usuario}', [BitacoraController::class, 'porUsuario']);
        Route::get('bitacoras/exportar/csv', [BitacoraController::class, 'exportarCSV']);
        Route::get('bitacoras', [BitacoraController::class, 'index']);
        Route::get('bitacoras/{bitacora}', [BitacoraController::class, 'show']);
        Route::apiResource('gestion-academica', GestionAcademicaController::class, ['only' => ['index', 'show']]);

        // CU9 - Consultar Disponibilidad de Aulas
        Route::get('aulas-disponibilidad', [AulasController::class, 'disponibilidad']);

        // CU10 - Tablero Administrativo (Dashboard)
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos']);
        Route::get('dashboard/catalogos', [DashboardController::class, 'catalogos']);
    });

    /**
     * RUTAS DOCENTE
     * Puede: Ver su horario, registrar asistencia, consultar disponibilidad de aulas, ver sus reportes
     */
    Route::middleware('IsDocente')->group(function () {
        // CU8 - Consultar Horario Semanal (Mi Horario)
        Route::get('mi-horario', [HorarioDocenteController::class, 'miHorario']);

        // Solo lectura de horarios y asistencias
        Route::get('horarios', [HorarioController::class, 'index']);
        Route::get('horarios/{horario}', [HorarioController::class, 'show']);
        Route::get('asistencias', [AsistenciaController::class, 'index']);
        Route::post('asistencias', [AsistenciaController::class, 'store']);

        // CU9 - Consultar Disponibilidad de Aulas
        Route::get('aulas-disponibilidad', [AulasController::class, 'disponibilidad']);
    });

    // Rutas compartidas disponibles para todos autenticados
    // (ya no es necesario porque usuarios ya está en IsAdmin)
});
