# 🛠️ COMANDOS ÚTILES DEL BACKEND

## 🚀 Iniciar el Servidor

```bash
# Terminal 1: Iniciar servidor Laravel
cd c:\backend2ex\backend
php artisan serve

# El servidor estará disponible en: http://localhost:8000
# API disponible en: http://localhost:8000/api
```

---

## 🗄️ Comandos de Base de Datos

```bash
# Ver estado de las migraciones
php artisan migrate:status

# Ejecutar todas las migraciones
php artisan migrate

# Rollback (deshacer) última migración
php artisan migrate:rollback

# Rollback de todas las migraciones
php artisan migrate:reset

# Rollback + Migrate (reiniciar BD)
php artisan migrate:refresh

# Rollback + Migrate + Seed
php artisan migrate:refresh --seed

# Ejecutar migraciones desde cero
php artisan migrate:fresh

# Ejecutar migraciones con salida detallada
php artisan migrate --verbose
```

---

## 🧪 Comandos de Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test tests/Feature/ApiEndpointsTest.php

# Ejecutar un test específico
php artisan test tests/Feature/ApiEndpointsTest.php::test_get_all_carreras

# Tests con salida detallada
php artisan test --verbose

# Tests sin cobertura (más rápido)
php artisan test --no-coverage

# Tests en paralelo (más rápido)
php artisan test --parallel

# Ver qué tests pasaron/fallaron
php artisan test --compact
```

---

## 🗂️ Comandos de Rutas

```bash
# Ver todas las rutas registradas
php artisan route:list

# Ver solo rutas API
php artisan route:list --path=/api

# Ver rutas con métodos específicos
php artisan route:list --method=POST
php artisan route:list --method=GET
php artisan route:list --method=PUT
php artisan route:list --method=DELETE

# Exportar rutas a JSON
php artisan route:list --json > routes.json
```

---

## 📦 Comandos de Controladores

```bash
# Generar un nuevo controlador (sin métodos)
php artisan make:controller NuevoController

# Generar controlador con métodos CRUD
php artisan make:controller NuevoController --resource

# Generar controlador con modelo
php artisan make:controller NuevoController --model=Modelo

# Generar API controlador (sin create/edit)
php artisan make:controller NuevoController --resource --api

# Generar todo: modelo, migración, factory, y controller
php artisan make:model Modelo -mcr
```

---

## 🎯 Comandos de Modelos

```bash
# Generar modelo
php artisan make:model Modelo

# Generar modelo con migración
php artisan make:model Modelo -m

# Generar modelo con factory
php artisan make:model Modelo -f

# Generar modelo con migraciones, factories y seeders
php artisan make:model Modelo -mfs

# Generar modelo con controller y migración
php artisan make:model Modelo -cm
```

---

## 🏭 Comandos de Factories y Seeders

```bash
# Generar una factory
php artisan make:factory CarreraFactory

# Generar seeder
php artisan make:seeder CarreraSeeder

# Ejecutar seeders
php artisan db:seed

# Ejecutar seeder específico
php artisan db:seed --class=CarreraSeeder
```

---

## 🔍 Comandos de Debugging

```bash
# Abrir consola interactiva de Laravel (Tinker)
php artisan tinker

# Ejemplos en Tinker:
# > \App\Models\Carrera::all()
# > \App\Models\Carrera::create(['nombre' => 'Test', 'codigo' => 'TEST'])
# > \App\Models\Carrera::find(1)
```

---

## 🧹 Comandos de Limpieza y Cache

```bash
# Limpiar cache de configuración
php artisan config:clear

# Limpiar cache de rutas
php artisan route:clear

# Limpiar cache de vistas
php artisan view:clear

# Limpiar todos los caches
php artisan cache:clear

# Limpiar todo
php artisan optimize:clear
```

---

## 📋 Comandos de Información

```bash
# Ver versión de Laravel
php artisan --version

# Ver información del proyecto
php artisan about

# Ver migraciones pendientes
php artisan migrate:status

# Ver seeders disponibles
php artisan seed:status

# Listar todos los comandos
php artisan list

# Ver ayuda de un comando específico
php artisan help migrate
php artisan help make:controller
```

---

## 💻 PowerShell - Comandos para Probar API

```powershell
# GET - Listar todos los registros
Invoke-WebRequest -Uri "http://localhost:8000/api/carreras" -Method Get

# GET - Obtener un registro específico
Invoke-WebRequest -Uri "http://localhost:8000/api/carreras/1" -Method Get

# POST - Crear nuevo registro
$body = @{
    nombre = "Ingeniería en Sistemas"
    codigo = "IS101"
    descripcion = "Test"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/api/carreras" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"

# PUT - Actualizar registro
$body = @{
    nombre = "Ingeniería Actualizada"
    codigo = "IS102"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/api/carreras/1" `
    -Method Put `
    -Body $body `
    -ContentType "application/json"

# DELETE - Eliminar registro
Invoke-WebRequest -Uri "http://localhost:8000/api/carreras/1" -Method Delete
```

---

## 🔧 Workflow Típico de Desarrollo

```bash
# 1. Crear modelo con todas las opciones
php artisan make:model Materia -mcfs

# 2. Editar migración (database/migrations)
# 3. Editar factory (database/factories)
# 4. Editar seeder (database/seeders)

# 5. Ejecutar migraciones
php artisan migrate:fresh --seed

# 6. Generar controller
php artisan make:controller MateriaController --model=Materia --resource

# 7. Editar controller (app/Http/Controllers)

# 8. Editar rutas (routes/api.php)

# 9. Probar endpoints con Postman o PowerShell

# 10. Escribir tests (tests/Feature)

# 11. Ejecutar tests
php artisan test
```

---

## 🚀 Quick Start - Para Continuar Desarrollo

```bash
# Terminal 1: Iniciar servidor
cd c:\backend2ex\backend
php artisan serve

# Terminal 2: Ejecutar tests automáticamente
cd c:\backend2ex\backend
php artisan test --watch

# Terminal 3: Usar Tinker para debugging
cd c:\backend2ex\backend
php artisan tinker
```

---

## 📱 Configurar React Frontend

```bash
# Terminal 3 (cuando esté listo frontend):
cd c:\frontend  (o la carpeta del frontend)
npm install
npm run dev

# El frontend estará en: http://localhost:5173 o http://localhost:3000
```

---

## ⚠️ Errores Comunes y Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| `SQLSTATE[08006]` | Conexión a BD fallida | Verificar `.env` con credenciales PostgreSQL |
| `Column not found` | Campo faltante en tabla | Ejecutar nueva migración o editar existente |
| `404 Not Found` | Ruta no existe | Verificar `routes/api.php` y ejecutar `php artisan route:list` |
| `Model not found` | Modelo no existe | Crear con `php artisan make:model Nombre` |
| `Foreign key constraint` | Relación incorrecta | Verificar IDs y relaciones en migraciones |
| `Class not found` | Namespace incorrecto | Verificar `use` statement y namespaces |
| `Port 8000 in use` | Servidor ya corriendo | Detener con `Ctrl+C` o `Get-Process php \| Stop-Process` |

---

## 🎯 Archivos Importantes a Recordar

```
.env                              ← Configuración de conexión a BD
app/Models/                       ← Tus modelos
app/Http/Controllers/             ← Tus controllers
routes/api.php                    ← Definición de rutas API
database/migrations/              ← Scripts de creación de tablas
database/factories/               ← Datos de prueba
tests/Feature/                    ← Tests de integración
config/cors.php                   ← Configuración de CORS
config/database.php               ← Configuración de BD
```

---

## 📚 Recursos Útiles

- [Documentación Laravel](https://laravel.com/docs)
- [Documentación de Eloquent](https://laravel.com/docs/eloquent)
- [API Resource Documentation](https://laravel.com/docs/eloquent-resources)
- [Testing Documentation](https://laravel.com/docs/testing)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

**¡Listo para desarrollar! 🚀**
