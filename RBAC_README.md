# Sistema de Gestión de Roles y Permisos (RBAC)

## CU13 - Gestionar Roles y Permisos

Este módulo implementa un sistema completo de Control de Acceso Basado en Roles (RBAC) para la gestión académica.

## Estructura del Sistema

### 1. **Tablas Creadas**
- `modulos`: Módulos del sistema (Aulas, Materias, Grupos, etc.)
- `acciones`: Acciones disponibles (crear, ver, editar, eliminar, etc.)
- `permisos`: Combinación de módulo + acción (ej: aulas.crear, materias.editar)
- `roles`: Roles del sistema
- `rol_permiso`: Relación roles - permisos
- `usuario_rol`: Relación usuarios - roles (un usuario puede tener múltiples roles)
- `politicas`: Políticas adicionales
- `rol_politica`: Relación roles - políticas

### 2. **Modelos Creados**
- `Modulo.php`
- `Accion.php`
- `Permiso.php`
- `Rol.php`
- `Politica.php`
- Usuario actualizado con relaciones RBAC

### 3. **Controlador**
- `RolPermisoController.php` - CRUD completo de roles y permisos

## Instalación

### 1. Ejecutar Migración
```bash
php artisan migrate
```

### 2. Ejecutar Seeder (IMPORTANTE: Aún NO ejecutar)
```bash
php artisan db:seed --class=RBACSeeder
```

⚠️ **NOTA**: El seeder creará:
- 7 acciones genéricas (crear, ver, editar, eliminar, exportar, importar, gestionar_estado)
- 11 módulos del sistema
- ~60 permisos (módulo + acción)
- 5 roles del sistema:
  - Super Administrador (todos los permisos)
  - Administrador (todos los permisos)
  - Coordinador (permisos de gestión)
  - Autoridad (solo lectura y exportación)
  - Docente (acceso muy limitado)

## Uso del Sistema

### Endpoints Disponibles

#### **Gestión de Roles**
```
GET    /api/roles              - Listar roles
POST   /api/roles              - Crear nuevo rol
GET    /api/roles/{id}         - Ver rol específico
PUT    /api/roles/{id}         - Actualizar rol
DELETE /api/roles/{id}         - Eliminar rol (no del sistema)
```

#### **Catálogos**
```
GET /api/permisos   - Listar todos los permisos agrupados por módulo
GET /api/modulos    - Listar módulos del sistema
GET /api/acciones   - Listar acciones disponibles
```

#### **Asignación de Roles a Usuarios**
```
POST   /api/usuarios/{userId}/roles         - Asignar rol a usuario
DELETE /api/usuarios/{userId}/roles/{rolId} - Remover rol de usuario
```

### Ejemplos de Uso

#### 1. Listar Permisos Disponibles
```bash
GET /api/permisos
```
Respuesta:
```json
{
  "success": true,
  "data": {
    "aulas": [
      {
        "id": 1,
        "nombre": "aulas.crear",
        "descripcion": "Crear en Gestión de aulas",
        "modulo": {...},
        "accion": {...}
      }
    ],
    "materias": [...]
  },
  "total": 60
}
```

#### 2. Crear Nuevo Rol
```bash
POST /api/roles
{
  "nombre": "Asistente Académico",
  "descripcion": "Asistente con permisos limitados",
  "permisos": [1, 2, 3, 5, 10, 15]  // IDs de permisos
}
```

#### 3. Asignar Rol a Usuario
```bash
POST /api/usuarios/5/roles
{
  "rol_id": 3
}
```

#### 4. Ver Rol con sus Permisos
```bash
GET /api/roles/3
```
Respuesta:
```json
{
  "success": true,
  "data": {
    "id": 3,
    "nombre": "Coordinador",
    "descripcion": "Coordinador académico con permisos de gestión",
    "es_sistema": true,
    "activo": true,
    "permisos": [
      {
        "id": 1,
        "nombre": "aulas.crear",
        "modulo": { "nombre": "aulas", "descripcion": "Gestión de aulas" },
        "accion": { "nombre": "crear", "descripcion": "Crear nuevo registro" }
      },
      ...
    ],
    "usuarios": [
      {
        "id": 2,
        "nombre": "Juan Coordinador",
        "email": "juan@coordinador.com"
      }
    ]
  }
}
```

## Verificación de Permisos en el Código

### En Modelos (User)
```php
// Verificar si tiene un rol específico
if ($user->tieneRol('Administrador')) {
    // ...
}

// Verificar si tiene un permiso específico
if ($user->tienePermiso('aulas.crear')) {
    // ...
}

// Verificar si puede hacer algo en un módulo
if ($user->puedeEn('aulas', 'crear')) {
    // ...
}

// Obtener todos los permisos del usuario
$permisos = $user->obtenerPermisos();
```

### En Middleware (Futuro)
Se puede crear un middleware personalizado:
```php
Route::middleware(['auth:sanctum', 'can:aulas.crear'])->group(function () {
    Route::post('/aulas', [AulasController::class, 'store']);
});
```

## Reglas de Negocio

### Roles del Sistema
- **No se pueden eliminar** (es_sistema = true)
- **No se puede cambiar su nombre**
- **Solo se pueden modificar sus permisos**
- Incluyen: Super Administrador, Administrador, Coordinador, Autoridad, Docente

### Roles Personalizados
- Pueden ser creados por administradores
- Pueden ser editados completamente
- **No se pueden eliminar si están asignados a usuarios**
- Se registran todas las operaciones en bitácora

### Permisos
- Son generados automáticamente por el seeder
- Formato: `modulo.accion` (ej: aulas.crear, materias.editar)
- Un permiso pertenece a UN módulo y UNA acción
- Los permisos se asignan a roles, no directamente a usuarios

### Asignación de Roles
- Un usuario puede tener **múltiples roles**
- Los permisos se acumulan de todos los roles
- Se registra quién asignó el rol y cuándo
- Se registra en bitácora

## Próximos Pasos

1. ✅ Migraciones creadas
2. ✅ Modelos creados
3. ✅ Controlador creado
4. ✅ Rutas configuradas
5. ✅ Seeder creado
6. ⏳ **NO ejecutar seeder aún** (esperar confirmación)
7. ⏳ Crear componentes frontend para gestión de roles
8. ⏳ Implementar middleware de permisos
9. ⏳ Crear políticas personalizadas

## Frontend (Por Implementar)

Páginas necesarias:
- `/private/admin/roles` - Listar y gestionar roles
- `/private/admin/roles/crear` - Crear nuevo rol
- `/private/admin/roles/[id]/editar` - Editar rol y sus permisos
- `/private/admin/usuarios/[id]/roles` - Asignar/remover roles de usuarios

## Bitácora

Todas las operaciones quedan registradas:
- Creación de roles
- Edición de roles
- Eliminación de roles
- Asignación de roles a usuarios
- Remoción de roles de usuarios

## Soporte

Para más información, consultar:
- Diagrama de caso de uso (CU13)
- Documentación de Laravel Sanctum
- Patrón RBAC (Role-Based Access Control)
