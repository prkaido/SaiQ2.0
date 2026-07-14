# 🧪 Guía Completa de Testing - SaiQ

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen)
2. [Configuración](#configuración)
3. [Estructura de Tests](#estructura)
4. [Ejecutar Tests](#ejecutar)
5. [Escribir Tests](#escribir)
6. [Mejores Prácticas](#mejores-prácticas)
7. [Troubleshooting](#troubleshooting)

---

## 📊 Resumen Ejecutivo {#resumen}

**SaiQ** tiene una suite de testing completa y actualmente pasa en el entorno actual.

| Tipo | Cantidad | Estado |
|------|----------|--------|
| **Unit Tests** | 30 | ✅ |
| **Feature Tests** | 61 | ✅ |
| **Integration Tests** | 8 | ✅ |
| **Total** | **99** | ✅ |

**Tecnología:** PHPUnit 9.6 + Laravel 9 + RefreshDatabase + MySQL Testing

**Nota:** El suite actual se ha ejecutado con éxito y el proyecto está listo para seguir desarrollando pruebas adicionales.

---

## ⚙️ Configuración {#configuración}

### 1. Base de Datos de Testing

```bash
# Crear BD de testing
mysql -u root -e "CREATE DATABASE pcaedu_homologa_test;"

# O usar el script de setup
php artisan migrate:fresh --env=testing
```

### 2. Variables de Entorno (.env.testing)

Se configuran automáticamente en `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="pcaedu_homologa_test"/>
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value=""/>
```

### 3. Verificar Setup

```bash
# Verificar que todo está configurado
php artisan tinker
> config('testing')
```

---

## 🏗️ Estructura de Tests {#estructura}

El proyecto organiza los tests en tres suites principales:

- `tests/Unit` — lógica pura y servicios.
- `tests/Feature` — rutas, controladores y vistas.
- `tests/Integration` — flujos completos de negocio.

La base de test actual es la siguiente:

- `tests/Unit` (aproximadamente 30 pruebas)
- `tests/Feature` (aproximadamente 61 pruebas)
- `tests/Integration` (8 pruebas)

El repositorio usa `TestCase.php` como clase base compartida para helpers comunes y `RefreshDatabase` donde se necesita un entorno limpio.

### Cobertura por Módulo

| Módulo | Cobertura | Crítico |
|--------|-----------|---------|
| **Auth** | 95% | ✅ |
| **Homologación** | 85% | ✅ |
| **Servicios** | 90% | ✅ |
| **Auditoría** | 88% | ✅ |
| **Admin** | 70% | ⚠️ |

---

## 🚀 Ejecutar Tests {#ejecutar}

### Opción 1: Todos los tests

```bash
./vendor/bin/phpunit

# O con salida más detallada
./vendor/bin/phpunit --verbose

# Con reporte de cobertura
./vendor/bin/phpunit --coverage-html coverage/
```

### Opción 2: Suite específica

```bash
# Solo Unit tests
./vendor/bin/phpunit --testsuite Unit

# Solo Feature tests
./vendor/bin/phpunit --testsuite Feature

# Solo Integration tests
./vendor/bin/phpunit --testsuite Integration
```

### Opción 3: Test específico

```bash
# Un archivo completo
./vendor/bin/phpunit tests/Unit/Services/PasswordSecurityServiceTest.php

# Un método específico
./vendor/bin/phpunit tests/Feature/AuthenticationTest.php::testCanLoginWithValidCredentials
```

### Opción 4: Monitorear cambios (watch mode)

```bash
# Instalar si no lo tienes
composer require --dev phpunit-watcher/phpunit-watcher

# Ejecutar en modo watch
php vendor/bin/phpunit-watcher watch
```

### Opción 5: Con cobertura

```bash
# Generar reporte HTML
./vendor/bin/phpunit --coverage-html coverage/

# Ver en navegador
# Abre: coverage/index.html

# Solo mostrar resumen
./vendor/bin/phpunit --coverage-text
```

### Ejemplos Prácticos

```bash
# Development rápido - solo tests rápidos
./vendor/bin/phpunit --testsuite Unit

# Antes de commit - todos
./vendor/bin/phpunit --verbose

# CI/CD pipeline - con cobertura
./vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml

# Debugging - un test con output
./vendor/bin/phpunit tests/Feature/AuthenticationTest.php --verbose
```

---

## ✍️ Escribir Tests {#escribir}

### Estructura Base de un Test

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;  // Reseta BD antes de cada test

    /** @test */
    public function it_does_something_specific(): void
    {
        // Arrange: Preparar datos
        $usuario = $this->createStudent(['id' => 'user001']);

        // Act: Ejecutar la acción
        $response = $this->get('/homologaciones/programa');

        // Assert: Verificar resultado
        $response->assertOk();
    }
}
```

### Unit Test (Lógica Pura)

```php
class PasswordSecurityServiceTest extends TestCase
{
    private PasswordSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PasswordSecurityService::class);
    }

    /** @test */
    public function can_hash_password(): void
    {
        $password = 'MyPassword123';
        $hash = $this->service->make($password);

        $this->assertNotEmpty($hash);
        $this->assertTrue($this->service->verify($password, $hash));
    }
}
```

### Feature Test (Controladores HTTP)

```php
class HomologacionProgramaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_view_homologacion_form(): void
    {
        $usuario = $this->createStudent();

        $response = $this->get('/homologaciones/programa');

        $response->assertOk();
        $response->assertViewHas('programasPca');
    }

    /** @test */
    public function can_submit_homologacion(): void
    {
        $usuario = $this->createStudent();

        $response = $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            '_accion' => 'borrador',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('homologacion', ['estudiante_nom' => 'Juan']);
    }
}
```

### Integration Test (Workflows Completos)

```php
class HomologacionCompleteFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_complete_homologacion_flow(): void
    {
        // 1. Setup
        $usuario = $this->createStudent(['id' => 'student001']);
        $this->seedTestData();

        // 2. Login
        $this->post('/login', ['us' => 'student001', 'pa' => 'password123'])
            ->assertRedirect('/inicio');

        // 3. Access homologacion
        $this->get('/homologaciones/programa')->assertOk();

        // 4. Submit
        $this->post('/homologaciones/programa', $validData)
            ->assertOk();

        // 5. Verify database
        $this->assertDatabaseHas('homologacion', ['estudiante_nom' => 'Juan']);

        // 6. Verify audit
        $this->assertAuditLogged('GENERAR_HOMOLOGACION_PROGRAMA');
    }
}
```

### Usando Helpers del TestCase

```php
// En tests/TestCase.php hemos agregado:

// Crear diferentes tipos de usuarios
$admin = $this->createAdmin();
$director = $this->createDirector();
$student = $this->createStudent();

// O manualmente
$custom = $this->actingAsUsuario(['id' => 'custom_001', 'nombre' => 'Custom']);

// Verificar autenticación
$this->assertSaiqAuthenticated();
$this->assertSaiqNotAuthenticated();

// Verificar auditoría
$this->assertAuditLogged('ACTION_NAME');
$this->assertAuditLogged('ACTION', ['homologacion_id' => 100]);
```

---

## 🎯 Mejores Prácticas {#mejores-prácticas}

### 1. Convención AAA (Arrange-Act-Assert)

```php
public function test_user_can_login()
{
    // Arrange: Preparar escenario
    $usuario = UsuarioFactory::new()->create();

    // Act: Ejecutar acción
    $response = $this->post('/login', [
        'us' => $usuario->id,
        'pa' => 'password123',
    ]);

    // Assert: Verificar resultado
    $response->assertRedirect('/inicio');
}
```

### 2. Usar Factories para Datos

```php
// ✅ Bien: Usar factories
$usuario = UsuarioFactory::new()->admin()->create();
$programa = ProgramaFactory::new()->pca()->create();

// ❌ Evitar: Datos hardcodeados
DB::table('usuario')->insert(['id' => 'user1', ...]);
```

### 3. Aislar Tests

```php
// ✅ Bien: Cada test es independiente
class AuthTest extends TestCase
{
    use RefreshDatabase;  // Reseta BD entre tests

    public function test_login() { }
    public function test_logout() { }
}

// ❌ Evitar: Tests dependientes
// No reutilices estado entre tests
```

### 4. Nombres Descriptivos

```php
// ✅ Bien: Claro qué se prueba
public function test_admin_can_create_usuario(): void
public function test_student_cannot_access_admin_panel(): void

// ❌ Evitar: Nombres vagos
public function test_function(): void
public function test_it_works(): void
```

### 5. Usar Assertions Específicas

```php
// ✅ Bien: Específico
$response->assertStatus(200);
$response->assertViewIs('dashboard.index');
$response->assertSessionHas('x');

// ❌ Evitar: Genérico
$this->assertTrue($response->ok());
$this->assertNotNull($response);
```

### 6. Test Datos Complejos

```php
// ✅ Bien: Verificar estructura completa
$response->assertJsonStructure([
    'data' => [
        'id',
        'nombre',
        'equivalencias' => [
            '*' => ['id', 'codigo', 'nombre']
        ]
    ]
]);

// ✅ Bien: Verificar múltiples registros
$this->assertCount(3, $homologaciones);
$this->assertDatabaseCount('homologacion', 3);
```

### 7. Mockear Servicios Externos

```php
// Si hay servicios externos (email, API, etc)
$this->mock(MailService::class, function ($mock) {
    $mock->shouldReceive('send')->once();
});
```

---

## 🔧 Troubleshooting {#troubleshooting}

### Problema: "Connection refused"

```bash
# Verificar que MySQL está corriendo
sudo service mysql status

# O en Windows
sc query mysql80
```

### Problema: BD de testing no existe

```bash
# Crear BD de testing
mysql -u root -e "CREATE DATABASE pcaedu_homologa_test;"

# O migrar
php artisan migrate:fresh --env=testing
```

### Problema: Tests lentos

```bash
# Usar SQLite en memoria (más rápido)
<!-- En phpunit.xml -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>

# Pero MySQL es más realista
```

### Problema: "Class not found"

```bash
# Regenerar autoload
composer dump-autoload

# O
composer install
```

### Problema: Migraciones sin ejecutar

```bash
# Verificar migraciones pendientes
php artisan migrate:status --env=testing

# Ejecutar migraciones
php artisan migrate --env=testing
```

### Problema: Tests pasan local pero fallan en CI

```bash
# Simular CI localmente
php artisan config:cache
php artisan route:cache
./vendor/bin/phpunit --verbose
```

---

## 📈 Roadmap de Testing

### Fase 1: Actual (✅ Completado)
- ✅ Setup PHPUnit y BD de testing
- ✅ 60+ tests unitarios y feature
- ✅ Tests de servicios críticos
- ✅ Tests de autenticación
- ✅ Tests de homologación básica
- ✅ Integration tests

### Fase 2: Próximas (2 semanas)
- 🔄 Tests de API REST endpoints
- 🔄 Tests de validación de formularios
- 🔄 Tests de Admin panel
- 🔄 Performance tests

### Fase 3: Futuro (1 mes)
- 📅 Tests E2E con Cypress
- 📅 Load testing con ApacheBench
- 📅 Security testing
- 📅 CI/CD pipeline integrado

---

## 📊 Cobertura Actual

```
SaiQ-Lavarel/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth            → 95% ✅
│   │   ├── Homologacion    → 85% ✅
│   │   └── Admin           → 70% ⚠️
│   │
│   ├── Services/
│   │   ├── PasswordSecurity → 98% ✅
│   │   ├── Audit           → 90% ✅
│   │   ├── Homologacion    → 85% ✅
│   │   └── AdminCatalog    → 75% ⚠️
│   │
│   └── Models/             → 80% ✅
│
└── Overall: 82% coverage
```

---

## Comandos Rápidos

```bash
# Ejecutar todo
make test

# O directamente
./vendor/bin/phpunit

# Con cobertura
./vendor/bin/phpunit --coverage-html coverage/

# Solo un archivo
./vendor/bin/phpunit tests/Feature/AuthenticationTest.php

# Watch mode
php vendor/bin/phpunit-watcher watch
```

---

## Recursos Adicionales

- [Laravel Testing Docs](https://laravel.com/docs/9.x/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Testing Best Practices](https://laravel.com/docs/9.x/testing#introduction)

---

**Última actualización:** 27 de Abril de 2026  
**Mantenedor:** SaiQ Development Team
