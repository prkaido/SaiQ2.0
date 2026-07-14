# 📋 Plan de Implementación - Mejoras SaiQ-Laravel

## 🎯 Objetivo

Implementar validaciones, constraints de base de datos y Request classes para evitar duplicados y mejorar la integridad de datos.

**Estado actual de tests:** 99 tests y 281 assertions.

---

## ✅ Fase 1: Preparación (Hoy)

### 1.1 Revisar estado actual
```bash
# En la terminal MySQL/PHPMyAdmin, ejecutar scripts de validación
# Ver: scripts/validar_duplicados.sql
```

**Checklist:**
- [ ] Revisar si existen duplicados actuales
- [ ] Documentar los duplicados encontrados
- [ ] Crear backup de la BD

### 1.2 Archivos creados
- ✅ `ANALISIS_MEJORAS.md` - Análisis completo
- ✅ `scripts/validar_duplicados.sql` - Scripts de validación
- ✅ `database/migrations/2026_04_29_100000_add_database_constraints_and_indexes.php` - Migración de constraints
- ✅ `app/Http/Requests/StoreUsuarioRequest.php` - Validación usuarios
- ✅ `app/Http/Requests/StoreProgramaRequest.php` - Validación programas
- ✅ `app/Http/Requests/StoreAsignaturaRequest.php` - Validación asignaturas
- ✅ `app/Http/Requests/StoreInstitucionRequest.php` - Validación instituciones
- ✅ `app/Http/Requests/StoreEquivalenciaRequest.php` - Validación equivalencias

---

## 🔄 Fase 2: Migración de BD (1-2 horas)

### 2.1 Ejecutar migración de constraints

```bash
# En terminal del proyecto
php artisan migrate --env=production

# O solo esta migración
php artisan migrate:refresh --path=database/migrations/2026_04_29_100000_add_database_constraints_and_indexes.php
```

**Verificar:**
```bash
# Ver tablas y constraints
php artisan tinker
# En Tinker:
> DB::select("SHOW CREATE TABLE usuario\G")
> DB::select("SHOW CREATE TABLE programa\G")
```

**Checklist:**
- [ ] Migración ejecutada sin errores
- [ ] Índices creados
- [ ] Constraints activos

### 2.2 Limpiar duplicados si existen

```sql
-- Si hay duplicados, ejecutar antes de migración
-- Ver script en: scripts/validar_duplicados.sql

-- Ejemplo: Mantener solo el primer usuario duplicado
DELETE FROM usuario WHERE id IN (
    SELECT id FROM (
        SELECT id, ROW_NUMBER() OVER (PARTITION BY id ORDER BY id) as rn 
        FROM usuario
    ) t WHERE rn > 1
);
```

---

## 💻 Fase 3: Actualizar Controllers (2-3 horas)

### 3.1 Usuario Controller

**Archivo:** `app/Http/Controllers/Admin/UsuarioController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Services\AdminCatalogService;
use App\Services\PasswordSecurityService;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('admin.usuarios.index', [
            'usuarios' => DB::table('usuario')
                ->leftJoin('programa', 'programa.cod', '=', 'usuario.programa')
                ->where('usuario.tipo', '!=', 1)
                ->orderBy('usuario.id')
                ->select('usuario.*', 'programa.nombre as programa_nombre')
                ->get(),
            'programas' => DB::table('programa')->orderBy('nombre')->get(),
        ]);
    }

    public function store(
        StoreUsuarioRequest $request,  // ← CAMBIO: Usar Request class
        AdminCatalogService $service,
        PasswordSecurityService $passwords
    ) {
        // ← CAMBIO: Validación ya hecha por StoreUsuarioRequest
        $data = $request->validated();

        $firma = $service->storeSignature($request->file('fi'), $data['pr'], $data['no']);

        DB::table('usuario')->insert([
            'id' => $data['no'],
            'clave' => $passwords->make($data['co']),
            'tipo' => 2,
            'programa' => $data['pr'],
            'firma' => $firma,
            'activo' => 1,
        ]);

        $service->audit('CREATE_USER', ['nuevo_usuario' => $data['no'], 'programa' => $data['pr']]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }
}
```

**Cambios:**
- Importar `StoreUsuarioRequest`
- Cambiar parámetro a `StoreUsuarioRequest $request`
- Usar `$request->validated()` en lugar de `$request->validate()`
- Eliminar validación manual

### 3.2 Programa Controller

**Archivo:** `app/Http/Controllers/Admin/ProgramaController.php`

```php
use App\Http\Requests\StoreProgramaRequest;

public function store(
    StoreProgramaRequest $request,  // ← CAMBIO
    AdminCatalogService $service
) {
    $data = $request->validated();  // ← CAMBIO
    
    $isPca = (int) $data['pr'] === 1;
    $code = $isPca 
        ? strtoupper((string) $data['co']) 
        : $service->nextProgramCode((int) $data['pr']);

    // ← CAMBIO: Ya no necesita validar unicidad (Request class lo hace)
    // Pero mantener validación de formato PCA
    if ($isPca && strlen($code) !== 3) {
        return back()
            ->withErrors(['co' => 'El programa PCA debe tener codigo de tres cifras.'])
            ->withInput();
    }

    DB::table('programa')->insert([
        'cod' => $code,
        'nombre' => $data['no'],
        'nivel' => $data['ni'],
        'inst' => (string) $data['pr'],
        'enpca' => $isPca ? 1 : 0,
        'activo' => $data['ac'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service->audit('CREATE_PROGRAM', ['codigo' => $code, 'nombre' => $data['no']]);

    return redirect()->route('admin.programas.index')
        ->with('success', 'Programa creado exitosamente.');
}
```

### 3.3 Asignatura Controller

**Archivo:** `app/Http/Controllers/Admin/AsignaturaController.php`

```php
use App\Http\Requests\StoreAsignaturaRequest;

public function store(
    StoreAsignaturaRequest $request,  // ← CAMBIO
    AdminCatalogService $service
) {
    $data = $request->validated();  // ← CAMBIO

    // ← CAMBIO: Validación de programa ya hecha
    // Pero podemos mantener el left join para más contexto
    $program = DB::table('programa')->where('cod', $data['pr'])->first();
    
    // Validar que el plan pertenece al programa (si se proporciona)
    if ($data['pl']) {
        $plan = DB::table('plan')->where('id', $data['pl'])->first();
        if (!$plan || $plan->programa !== $program->cod) {
            return back()
                ->withErrors(['pl' => 'Plan no pertenece al programa.'])
                ->withInput();
        }
    }

    $codigo = $data['co'] ?? strtoupper(trim(substr($data['no'], 0, 3)));

    DB::table('asignatura')->insert([
        'cod' => $codigo,
        'codigo' => $data['co'],
        'nombre' => $data['no'],
        'programa' => $data['pr'],
        'plan' => $data['pl'],
        'nivel' => $data['ni'],
        'creditos' => $data['cr'],
        'ihsemana' => $data['is'],
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service->audit('CREATE_SUBJECT', ['codigo' => $codigo, 'nombre' => $data['no']]);

    return redirect()->route('admin.asignaturas.index')
        ->with('success', 'Asignatura creada exitosamente.');
}
```

### 3.4 Institución Controller

**Archivo:** `app/Http/Controllers/Admin/InstitucionController.php`

```php
use App\Http\Requests\StoreInstitucionRequest;

public function store(
    StoreInstitucionRequest $request,  // ← NUEVO
    AdminCatalogService $service
) {
    $data = $request->validated();

    DB::table('institucion')->insert([
        'nombre' => $data['no'],
        'abrev' => $data['ab'],
        'tipo' => $data['ti'],
        'ciudad' => $data['ci'],
        'pais' => $data['pa'],
        'activo' => $data['ac'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service->audit('CREATE_INSTITUTION', ['nombre' => $data['no']]);

    return redirect()->route('admin.instituciones.index')
        ->with('success', 'Institución creada exitosamente.');
}
```

### 3.5 Equivalencia Controller

**Archivo:** `app/Http/Controllers/Admin/EquivalenciaController.php`

```php
use App\Http\Requests\StoreEquivalenciaRequest;

public function store(
    StoreEquivalenciaRequest $request,  // ← NUEVO
    AdminCatalogService $service
) {
    $data = $request->validated();

    DB::table('equiv')->insert([
        'asg_pca' => $data['ap'],
        'asg_ext' => $data['ae'],
    ]);

    $service->audit('CREATE_EQUIVALENCE', [
        'asg_pca' => $data['ap'],
        'asg_ext' => $data['ae'],
    ]);

    return redirect()->route('admin.equivalencias.index')
        ->with('success', 'Equivalencia creada exitosamente.');
}
```

---

## 🧪 Fase 4: Testing (1-2 horas)

### 4.1 Crear tests para validación

**Archivo:** `tests/Feature/Admin/UsuarioValidationTest.php`

```php
<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsuarioValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_usuario_requiere_nombre()
    {
        $response = $this->post(route('admin.usuarios.store'), [
            'no' => '',
            'co' => 'password123',
            'pr' => 'PCA001',
        ]);

        $response->assertSessionHasErrors('no');
    }

    public function test_crear_usuario_duplicado_falla()
    {
        // Crear primer usuario
        $this->post(route('admin.usuarios.store'), [
            'no' => 'jsmith',
            'co' => 'password123',
            'pr' => 'PCA001',
        ]);

        // Intentar crear duplicado
        $response = $this->post(route('admin.usuarios.store'), [
            'no' => 'jsmith',
            'co' => 'password456',
            'pr' => 'PCA001',
        ]);

        $response->assertSessionHasErrors('no');
        $this->assertStringContainsString('ya existe', session('errors')->first('no'));
    }

    public function test_programa_invalido_rechazado()
    {
        $response = $this->post(route('admin.usuarios.store'), [
            'no' => 'jsmith',
            'co' => 'password123',
            'pr' => 'INVALID',
        ]);

        $response->assertSessionHasErrors('pr');
    }
}
```

### 4.2 Ejecutar tests

```bash
php artisan test tests/Feature/Admin/UsuarioValidationTest.php

# O ejecutar suite completa
php artisan test

# Con coverage
php artisan test --coverage
```

> Nota: la suite actual del proyecto contiene 99 tests y 281 assertions.

---

## 📊 Fase 5: Validación Final (1 hora)

### 5.1 Verificar cambios

```bash
# Ejecutar scripts de validación nuevamente
mysql -u root pcaedu_homologa < scripts/validar_duplicados.sql

# Ver que no hay duplicados
```

### 5.2 Pruebas manuales

1. **Crear usuario duplicado:** Debería rechazar
2. **Crear programa con código existente:** Debería rechazar
3. **Crear asignatura con programa inválido:** Debería rechazar
4. **Crear institución con nombre duplicado:** Debería rechazar

### 5.3 Checklist Final

- [ ] Todos los controllers usan Request classes
- [ ] Migraciones ejecutadas
- [ ] Tests pasando
- [ ] Sin duplicados en BD
- [ ] Índices creados
- [ ] Documentación actualizada

---

## 🚀 Deploy a Producción

```bash
# Backup de BD
mysqldump -u root pcaedu_homologa > backup_$(date +%Y%m%d_%H%M%S).sql

# Migrar
php artisan migrate --force

# Validar
mysql -u root pcaedu_homologa < scripts/validar_duplicados.sql

# Tests finales
php artisan test
```

---

## 📝 Rollback si es necesario

```bash
# Revertir última migración
php artisan migrate:rollback

# Restaurar BD desde backup
mysql -u root pcaedu_homologa < backup_YYYYMMDD_HHMMSS.sql
```

---

## 📚 Archivos Relacionados

- [ANALISIS_MEJORAS.md](ANALISIS_MEJORAS.md) - Análisis detallado
- [scripts/validar_duplicados.sql](scripts/validar_duplicados.sql) - Validación
- [database/migrations/2026_04_29_100000_add_database_constraints_and_indexes.php]
- [app/Http/Requests/*](app/Http/Requests) - Request classes

---

**Última actualización:** 29/04/2026
**Estado:** Listo para implementar
