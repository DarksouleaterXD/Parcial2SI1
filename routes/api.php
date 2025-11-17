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
use App\Http\Controllers\ImportacionUsuariosController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\ReporteController;

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

        // CU4 - Gestionar Carreras (solo admin)
        Route::apiResource('carreras', CarreraController::class);
        Route::get('carreras-lista', [CarreraController::class, 'lista']);

        // CU6 - Gestionar Periodos Académicos (solo admin puede crear/editar)
        Route::apiResource('periodos', PeriodoController::class);
        Route::patch('periodos/{periodo}/vigente', [PeriodoController::class, 'marcarVigente']);

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

        // CU12 - Importar Usuarios en Lote (DEBE IR ANTES de apiResource)
        Route::get('usuarios/importar/plantilla', [ImportacionUsuariosController::class, 'descargarPlantilla']);
        Route::post('usuarios/importar/validar', [ImportacionUsuariosController::class, 'validarArchivo']);
        Route::post('usuarios/importar/confirmar', [ImportacionUsuariosController::class, 'confirmarImportacion']);
        Route::get('usuarios/importar/historial', [ImportacionUsuariosController::class, 'historial']);

        // Gestión completa de usuarios (CRUD)
        Route::apiResource('usuarios', UserController::class);

        // CU11 - Generar/Exportar Reportes (solo admin)
        Route::get('reportes/horarios-semanales', [ReporteController::class, 'horariosSemanales']);
        Route::get('reportes/horarios-semanales/pdf', [ReporteController::class, 'horariosSemanalesPDF']);
        Route::get('reportes/horarios-semanales/excel', [ReporteController::class, 'horariosSemanalesExcel']);
        Route::get('reportes/aulas-disponibles', [ReporteController::class, 'aulasDisponibles']);
        Route::get('reportes/aulas-disponibles/pdf', [ReporteController::class, 'aulasDisponiblesPDF']);
        Route::get('reportes/aulas-disponibles/excel', [ReporteController::class, 'aulasDisponiblesExcel']);

        // CU16 - Reportes de Asistencias (solo admin)
        Route::prefix('reportes/asistencias')->group(function () {
            Route::get('/', [App\Http\Controllers\ReporteAsistenciaController::class, 'reporteGeneral']);
            Route::get('/estadisticas-docente', [App\Http\Controllers\ReporteAsistenciaController::class, 'estadisticasPorDocente']);
            Route::get('/pendientes', [App\Http\Controllers\ReporteAsistenciaController::class, 'asistenciasPendientes']);
            Route::get('/resumen', [App\Http\Controllers\ReporteAsistenciaController::class, 'resumenGeneral']);
            Route::get('/pdf', [App\Http\Controllers\ReporteAsistenciaController::class, 'exportarPDF']);
            Route::get('/excel', [App\Http\Controllers\ReporteAsistenciaController::class, 'exportarExcel']);
        });
    });

    /**
     * RUTAS COMPARTIDAS: ADMIN Y COORDINADOR
     * Ambos roles tienen acceso completo a estos recursos
     */
    Route::middleware('IsAdminOrCoordinador')->group(function () {
        // CU4 - Gestionar Aulas
        Route::apiResource('aulas', AulasController::class);
        Route::patch('aulas/{aula}/estado', [AulasController::class, 'updateEstado']);

        // CU9 - Consultar Disponibilidad de Aulas
        Route::get('aulas-disponibilidad', [AulasController::class, 'disponibilidad']);

        // CU7 - Gestionar Horarios (Admin y Coordinador)
        Route::apiResource('bloques-horarios', BloqueHorarioController::class);
        Route::patch('bloques-horarios/{bloqueHorario}/estado', [BloqueHorarioController::class, 'updateEstado']);
        Route::apiResource('horarios', HorarioController::class);

        // CU7 - Gestionar Carga Horaria
        Route::apiResource('carga-horaria', CargaHorariaController::class);
        Route::post('carga-horaria/por-grupo-periodo', [CargaHorariaController::class, 'porGrupoPeriodo']);
        Route::post('carga-horaria/por-docente-periodo', [CargaHorariaController::class, 'porDocentePeriodo']);

        // CU5 - Gestionar Grupos
        Route::apiResource('grupos', GrupoController::class);

        // CU3 - Gestionar Materias
        Route::apiResource('materias', MateriaController::class);
        Route::patch('materias/{materia}/estado', [MateriaController::class, 'updateEstado']);

        // CU2 - Gestionar Docentes
        Route::apiResource('docentes', DocenteController::class);
        Route::patch('/docentes/{docente}/estado', [DocenteController::class, 'updateEstado']);

        // CU10 - Tablero Administrativo (Dashboard)
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos']);
        Route::get('dashboard/catalogos', [DashboardController::class, 'catalogos']);

        // CU8 - Consultar Horario Semanal (Coordinador consulta horario de docentes)
        Route::get('docentes-horarios', [HorarioDocenteController::class, 'listadoDocentesHorarios']);
        Route::get('horario-docente/{id_docente}', [HorarioDocenteController::class, 'horarioDocente']);

        // CU16 - Validar Asistencias (Coordinador)
        Route::get('asistencias/pendientes', [AsistenciaController::class, 'pendientesValidacion']);
        Route::patch('asistencias/{id}/validar', [AsistenciaController::class, 'validar']);

        // Gestión de sesiones
        Route::apiResource('sesiones', SesionController::class);
        Route::post('sesiones/generar', [SesionController::class, 'generarSesiones']);
        Route::patch('sesiones/{id}/cancelar', [SesionController::class, 'cancelar']);
    });

    /**
     * RUTAS COMPARTIDAS: ADMIN, COORDINADOR Y AUTORIDAD
     * Dashboard solo para roles administrativos (sin docente)
     */
    Route::middleware('IsAdminOrCoordinadorOrAutoridad')->group(function () {
        // CU10 - Tablero Administrativo (Dashboard)
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos']);
        Route::get('dashboard/catalogos', [DashboardController::class, 'catalogos']);
    });

    /**
     * RUTAS COMPARTIDAS: ADMIN, COORDINADOR, AUTORIDAD Y DOCENTE
     * Recursos de consulta (lectura) accesibles para todos los roles del sistema
     */
    Route::middleware('IsAnyAuthenticated')->group(function () {
        
        // Consultas de solo lectura compartidas
        Route::get('horarios', [HorarioController::class, 'index']);
        Route::get('horarios/{horario}', [HorarioController::class, 'show']);
        Route::get('aulas', [AulasController::class, 'index']);
        Route::get('aulas/{aula}', [AulasController::class, 'show']);
        Route::get('materias', [MateriaController::class, 'index']);
        Route::get('materias/{materia}', [MateriaController::class, 'show']);
        Route::get('docentes', [DocenteController::class, 'index']);
        Route::get('docentes/{docente}', [DocenteController::class, 'show']);
        Route::get('grupos', [GrupoController::class, 'index']);
        Route::get('grupos/{grupo}', [GrupoController::class, 'show']);
        Route::get('periodos', [PeriodoController::class, 'index']);
        Route::get('periodos/{periodo}', [PeriodoController::class, 'show']);
        Route::get('carreras', [CarreraController::class, 'index']);
        Route::get('carreras/{carrera}', [CarreraController::class, 'show']);
        Route::get('carreras-lista', [CarreraController::class, 'lista']);
        Route::get('bloques-horarios', [BloqueHorarioController::class, 'index']);
        Route::get('bloques-horarios/{bloqueHorario}', [BloqueHorarioController::class, 'show']);
        
        // Bitácora (solo lectura)
        Route::get('bitacoras/seed/datos-prueba', [BitacoraController::class, 'seedDatos']);
        Route::get('bitacoras/estadisticas/resumen', [BitacoraController::class, 'estadisticas']);
        Route::get('bitacoras/tabla/{tabla}', [BitacoraController::class, 'porTabla']);
        Route::get('bitacoras/usuario/{id_usuario}', [BitacoraController::class, 'porUsuario']);
        Route::get('bitacoras/exportar/csv', [BitacoraController::class, 'exportarCSV']);
        Route::get('bitacoras', [BitacoraController::class, 'index']);
        Route::get('bitacoras/{bitacora}', [BitacoraController::class, 'show']);
        
        // Gestión académica (solo lectura)
        Route::get('gestion-academica', [GestionAcademicaController::class, 'index']);
        Route::get('gestion-academica/{gestionAcademica}', [GestionAcademicaController::class, 'show']);
        
        // CU9 - Consultar Disponibilidad de Aulas
        Route::get('aulas-disponibilidad', [AulasController::class, 'disponibilidad']);
        
        // Gestión de usuarios (solo lectura)
        Route::get('usuarios', [UserController::class, 'index']);
        Route::get('usuarios/{usuario}', [UserController::class, 'show']);
        
        // Reportes
        Route::get('reportes/horarios-semanales', [ReporteController::class, 'horariosSemanales']);
        Route::get('reportes/horarios-semanales/pdf', [ReporteController::class, 'horariosSemanalesPDF']);
        Route::get('reportes/horarios-semanales/excel', [ReporteController::class, 'horariosSemanalesExcel']);
        Route::get('reportes/aulas-disponibles', [ReporteController::class, 'aulasDisponibles']);
    });

    /**
     * RUTAS COORDINADOR (exclusivas)
     * NOTA: Las rutas comunes ya están en IsAdminOrCoordinador e IsAdminOrCoordinadorOrAutoridad
     */
    Route::middleware('IsCoordinador')->group(function () {
        // CU13 - Roles y Permisos (solo coordinador puede ver RBAC)
        Route::get('roles', [RolPermisoController::class, 'index']);
        Route::get('roles/{rol}', [RolPermisoController::class, 'show']);
        Route::get('permisos', [RolPermisoController::class, 'listarPermisos']);
        Route::get('modulos', [RolPermisoController::class, 'listarModulos']);
        Route::get('acciones', [RolPermisoController::class, 'listarAcciones']);
    });

    /**
     * RUTAS AUTORIDAD
     * Puede: Consultar (lectura) horarios, aulas, disponibilidad, ver reportes, bitácora
     */
    /**
     * RUTAS AUTORIDAD
     * NOTA: Autoridad ya tiene acceso a la mayoría de recursos través de:
     * - IsAdminOrCoordinador (recursos CRUD completo pero Autoridad puede usar GET)
     * - IsAdminOrCoordinadorOrAutoridad (dashboard)
     * - IsCoordinador (bitácoras, reportes, usuarios, carreras, periodos)
     * 
     * Este grupo solo contiene rutas EXCLUSIVAS para Autoridad (si las hay)
     */
    Route::middleware('IsAutoridad')->group(function () {
        // Por ahora no hay rutas exclusivas de Autoridad
        // Autoridad accede a todo mediante los middlewares compartidos
    });

    /**
     * RUTAS DOCENTE (exclusivas)
     * NOTA: Docente ya tiene acceso de lectura a horarios, periodos, aulas-disponibilidad 
     * a través de IsAdminOrCoordinadorOrAutoridad. Solo definir rutas exclusivas aquí.
     */
    Route::middleware('IsDocente')->group(function () {
        // CU8 - Consultar Horario Semanal (Mi Horario) - EXCLUSIVO DOCENTE
        Route::get('mi-horario', [HorarioDocenteController::class, 'miHorario']);

        // CU16 - Registrar Asistencia (Docente) - EXCLUSIVO DOCENTE
        Route::get('mis-clases-hoy', [AsistenciaController::class, 'misClasesHoy']);
        Route::post('asistencias', [AsistenciaController::class, 'store']);
        Route::get('asistencias', [AsistenciaController::class, 'index']); // Solo ve sus propias asistencias
        Route::patch('asistencias/{id}/observacion', [AsistenciaController::class, 'ajustarObservacion']);
    });

    // Rutas compartidas disponibles para todos autenticados
    // (ya no es necesario porque usuarios ya está en IsAdmin)
});
