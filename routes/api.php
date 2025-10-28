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

        // CU6 - Gestionar Periodos Académicos
        Route::apiResource('periodos', PeriodoController::class);
        Route::patch('periodos/{periodo}/vigente', [PeriodoController::class, 'marcarVigente']);

        // CU5 - Gestionar Grupos
        Route::apiResource('grupos', GrupoController::class);

        // CU4 - Gestionar Aulas
        Route::apiResource('aulas', AulasController::class);
        Route::patch('aulas/{aula}/estado', [AulasController::class, 'updateEstado']);

        // CU7 - Gestionar Horarios
        Route::apiResource('horarios', HorarioController::class);

        // CU20 - Bitácora (solo admin puede ver todo)
        Route::apiResource('bitacoras', BitacoraController::class);

        // CU6 - Gestión Académica
        Route::apiResource('gestion-academica', GestionAcademicaController::class);

        // Personas
        Route::apiResource('personas', PersonaController::class);
        Route::get('/personas/{persona}/bitacora', [PersonaController::class, 'bitacora']);
    });

    /**
     * RUTAS COORDINADOR
     * Puede: CU2 (Docentes), CU3 (Materias), CU4 (Aulas), CU5 (Grupos), CU6 (Periodos-lectura), CU7 (Horarios), CU14-15 (Asignación)
     */
    Route::middleware('IsCoordinador')->group(function () {
        Route::apiResource('materias', MateriaController::class);
        Route::patch('materias/{materia}/estado', [MateriaController::class, 'updateEstado']);
        Route::apiResource('docentes', DocenteController::class);
        Route::patch('/docentes/{docente}/estado', [DocenteController::class, 'updateEstado']);
        Route::apiResource('aulas', AulasController::class);
        Route::patch('aulas/{aula}/estado', [AulasController::class, 'updateEstado']);
        Route::apiResource('grupos', GrupoController::class);
        Route::apiResource('horarios', HorarioController::class);

        // CU6 - Coordinador solo puede consultar periodos
        Route::get('periodos', [PeriodoController::class, 'index']);
        Route::get('periodos/{periodo}', [PeriodoController::class, 'show']);
    });

    /**
     * RUTAS AUTORIDAD
     * Puede: Consultar (lectura) horarios, aulas, ver reportes, bitácora
     */
    Route::middleware('IsAutoridad')->group(function () {
        // Solo lectura para autoridad
        Route::get('horarios', [HorarioController::class, 'index']);
        Route::get('horarios/{horario}', [HorarioController::class, 'show']);
        Route::get('aulas', [AulasController::class, 'index']);
        Route::get('aulas/{aula}', [AulasController::class, 'show']);
        Route::apiResource('bitacoras', BitacoraController::class, ['only' => ['index', 'show']]);
        Route::apiResource('gestion-academica', GestionAcademicaController::class, ['only' => ['index', 'show']]);
    });

    /**
     * RUTAS DOCENTE
     * Puede: Ver su horario, registrar asistencia, ver sus reportes
     */
    Route::middleware('IsDocente')->group(function () {
        // Solo lectura de horarios y asistencias
        Route::get('horarios', [HorarioController::class, 'index']);
        Route::get('horarios/{horario}', [HorarioController::class, 'show']);
        Route::get('asistencias', [AsistenciaController::class, 'index']);
        Route::post('asistencias', [AsistenciaController::class, 'store']);
    });

    // Rutas compartidas disponibles para todos autenticados
    Route::apiResource('usuarios', UserController::class, ['only' => ['index', 'show']]);
});
