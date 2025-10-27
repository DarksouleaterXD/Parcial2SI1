<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\Grupo;
use App\Models\Aulas;
use App\Models\Horario;
use App\Models\Asistencia;
use App\Models\Persona;
use App\Models\Docente;
use App\Models\Bitacora;
use App\Models\GestionAcademica;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test GET /api/carreras - Listar todas las carreras
     */
    public function test_get_all_carreras()
    {
        Carrera::factory()->count(3)->create();

        $response = $this->getJson('/api/carreras');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'nombre', 'codigo', 'descripcion', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/carreras - Crear nueva carrera
     */
    public function test_create_carrera()
    {
        $data = [
            'nombre' => 'Ingeniería en Sistemas',
            'codigo' => 'IS101',
            'descripcion' => 'Carrera de sistemas computacionales'
        ];

        $response = $this->postJson('/api/carreras', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment($data);
        $this->assertDatabaseHas('carreras', $data);
    }

    /**
     * Test GET /api/carreras/{id} - Obtener una carrera específica
     */
    public function test_show_carrera()
    {
        $carrera = Carrera::factory()->create();

        $response = $this->getJson("/api/carreras/{$carrera->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $carrera->id, 'nombre' => $carrera->nombre]);
    }

    /**
     * Test PUT /api/carreras/{id} - Actualizar carrera
     */
    public function test_update_carrera()
    {
        $carrera = Carrera::factory()->create();
        $data = ['nombre' => 'Ingeniería Actualizada'];

        $response = $this->putJson("/api/carreras/{$carrera->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('carreras', ['id' => $carrera->id, 'nombre' => 'Ingeniería Actualizada']);
    }

    /**
     * Test DELETE /api/carreras/{id} - Eliminar carrera
     */
    public function test_delete_carrera()
    {
        $carrera = Carrera::factory()->create();

        $response = $this->deleteJson("/api/carreras/{$carrera->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('carreras', ['id' => $carrera->id]);
    }

    /**
     * Test GET /api/materias - Listar todas las materias
     */
    public function test_get_all_materias()
    {
        Materia::factory()->count(3)->create();

        $response = $this->getJson('/api/materias');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'nombre', 'codigo', 'creditos', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/materias - Crear nueva materia
     */
    public function test_create_materia()
    {
        $data = [
            'nombre' => 'Programación I',
            'codigo' => 'PROG101',
            'creditos' => 4
        ];

        $response = $this->postJson('/api/materias', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment($data);
    }

    /**
     * Test GET /api/materias/{id} - Obtener materia específica
     */
    public function test_show_materia()
    {
        $materia = Materia::factory()->create();

        $response = $this->getJson("/api/materias/{$materia->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $materia->id]);
    }

    /**
     * Test PUT /api/materias/{id} - Actualizar materia
     */
    public function test_update_materia()
    {
        $materia = Materia::factory()->create();
        $data = ['nombre' => 'Programación II'];

        $response = $this->putJson("/api/materias/{$materia->id}", $data);

        $response->assertStatus(200);
    }

    /**
     * Test DELETE /api/materias/{id} - Eliminar materia
     */
    public function test_delete_materia()
    {
        $materia = Materia::factory()->create();

        $response = $this->deleteJson("/api/materias/{$materia->id}");

        $response->assertStatus(204);
    }

    /**
     * Test GET /api/grupos - Listar todos los grupos
     */
    public function test_get_all_grupos()
    {
        Grupo::factory()->count(3)->create();

        $response = $this->getJson('/api/grupos');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'numero_grupo', 'semestre', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/grupos - Crear nuevo grupo
     */
    public function test_create_grupo()
    {
        $data = [
            'numero_grupo' => 1,
            'semestre' => 1
        ];

        $response = $this->postJson('/api/grupos', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/aulas - Listar todas las aulas
     */
    public function test_get_all_aulas()
    {
        Aulas::factory()->count(3)->create();

        $response = $this->getJson('/api/aulas');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'numero_aula', 'capacidad', 'ubicacion', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/aulas - Crear nueva aula
     */
    public function test_create_aula()
    {
        $data = [
            'numero_aula' => '101',
            'capacidad' => 40,
            'ubicacion' => 'Bloque A'
        ];

        $response = $this->postJson('/api/aulas', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/horarios - Listar todos los horarios
     */
    public function test_get_all_horarios()
    {
        Horario::factory()->count(3)->create();

        $response = $this->getJson('/api/horarios');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'dia_semana', 'hora_inicio', 'hora_fin', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/horarios - Crear nuevo horario
     */
    public function test_create_horario()
    {
        $grupo = Grupo::factory()->create();
        $aula = Aulas::factory()->create();

        $data = [
            'id_grupo' => $grupo->id,
            'id_aula' => $aula->id,
            'dia_semana' => 'Lunes',
            'hora_inicio' => '08:00',
            'hora_fin' => '09:30'
        ];

        $response = $this->postJson('/api/horarios', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/asistencias - Listar todas las asistencias
     */
    public function test_get_all_asistencias()
    {
        Asistencia::factory()->count(3)->create();

        $response = $this->getJson('/api/asistencias');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'fecha', 'asistio', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/asistencias - Registrar asistencia
     */
    public function test_create_asistencia()
    {
        $horario = Horario::factory()->create();
        $docente = Docente::factory()->create();

        $data = [
            'id_horario' => $horario->id,
            'id_docente' => $docente->id,
            'fecha' => now()->toDateString(),
            'asistio' => true
        ];

        $response = $this->postJson('/api/asistencias', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/personas - Listar todas las personas
     */
    public function test_get_all_personas()
    {
        Persona::factory()->count(3)->create();

        $response = $this->getJson('/api/personas');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'nombre', 'apellido', 'cedula', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/personas - Crear nueva persona
     */
    public function test_create_persona()
    {
        $data = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'cedula' => '12345678',
            'email' => 'juan@example.com',
            'telefono' => '1234567890'
        ];

        $response = $this->postJson('/api/personas', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/docentes - Listar todos los docentes
     */
    public function test_get_all_docentes()
    {
        Docente::factory()->count(3)->create();

        $response = $this->getJson('/api/docentes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'id_usuario', 'id_persona', 'activo', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/docentes - Crear nuevo docente
     */
    public function test_create_docente()
    {
        $user = User::factory()->create();
        $persona = Persona::factory()->create();

        $data = [
            'id_usuario' => $user->id,
            'id_persona' => $persona->id,
            'activo' => true
        ];

        $response = $this->postJson('/api/docentes', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/usuarios - Listar todos los usuarios
     */
    public function test_get_all_usuarios()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/usuarios');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'email', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/usuarios - Crear nuevo usuario
     */
    public function test_create_usuario()
    {
        $data = [
            'name' => 'Carlos',
            'email' => 'carlos@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/usuarios', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/bitacoras - Listar todas las bitácoras
     */
    public function test_get_all_bitacoras()
    {
        Bitacora::factory()->count(3)->create();

        $response = $this->getJson('/api/bitacoras');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'id_persona', 'accion', 'descripcion', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/bitacoras - Crear nueva entrada de bitácora
     */
    public function test_create_bitacora()
    {
        $persona = Persona::factory()->create();

        $data = [
            'id_persona' => $persona->id,
            'accion' => 'LOGIN',
            'descripcion' => 'Usuario inició sesión'
        ];

        $response = $this->postJson('/api/bitacoras', $data);

        $response->assertStatus(201);
    }

    /**
     * Test GET /api/gestion-academica - Listar gestiones académicas
     */
    public function test_get_all_gestion_academica()
    {
        GestionAcademica::factory()->count(3)->create();

        $response = $this->getJson('/api/gestion-academica');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'id_carrera', 'periodo', 'anio', 'activo', 'created_at', 'updated_at']
        ]);
    }

    /**
     * Test POST /api/gestion-academica - Crear nueva gestión académica
     */
    public function test_create_gestion_academica()
    {
        $carrera = Carrera::factory()->create();

        $data = [
            'id_carrera' => $carrera->id,
            'periodo' => 'I',
            'anio' => 2025,
            'activo' => true
        ];

        $response = $this->postJson('/api/gestion-academica', $data);

        $response->assertStatus(201);
    }

    /**
     * Test 404 - Recurso no encontrado
     */
    public function test_not_found()
    {
        $response = $this->getJson('/api/carreras/99999');

        $response->assertStatus(404);
    }

    /**
     * Test validación - Campos requeridos
     */
    public function test_validation_required_fields()
    {
        $response = $this->postJson('/api/carreras', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombre', 'codigo']);
    }
}
