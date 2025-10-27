<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Resources
Route::apiResource('carreras', CarreraController::class);
Route::apiResource('materias', MateriaController::class);
Route::apiResource('grupos', GrupoController::class);
Route::apiResource('aulas', AulasController::class);
Route::apiResource('horarios', HorarioController::class);
Route::apiResource('asistencias', AsistenciaController::class);
Route::apiResource('personas', PersonaController::class);
Route::apiResource('docentes', DocenteController::class);
Route::apiResource('usuarios', UserController::class);
Route::apiResource('bitacoras', BitacoraController::class);
Route::apiResource('gestion-academica', GestionAcademicaController::class);

// Rutas adicionales para relaciones
Route::get('/carreras/{carrera}/materias', [CarreraController::class, 'materias']);
Route::get('/grupos/{grupo}/docentes', [GrupoController::class, 'docentes']);
Route::get('/grupos/{grupo}/materias', [GrupoController::class, 'materias']);
Route::get('/docentes/{docente}/horarios', [DocenteController::class, 'horarios']);
Route::get('/personas/{persona}/bitacora', [PersonaController::class, 'bitacora']);
