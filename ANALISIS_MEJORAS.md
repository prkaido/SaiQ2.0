# 📊 Análisis del Proyecto SaiQ-Laravel - Recomendaciones de Mejora

**Fecha:** 29 de Abril de 2026  
**Estado:** Migración Laravel en progreso  
**Versión Laravel:** 9.19

---

## 🎯 Resumen Ejecutivo

El proyecto está en buen estado de migración de PHP legacy a Laravel, con testing suite implementado. Sin embargo, existen **problemas críticos de integridad de datos** y **oportunidades de optimización arquitectónica**.

### Prioridades
1. 🔴 **CRÍTICO:** Duplicados en base de datos y falta de constraints
2. 🟠 **ALTO:** Relaciones faltantes y validaciones débiles
3. 🟡 **MEDIO:** Refactoring arquitectónico y cleanup de código duplicado

---

## 🔴 PROBLEMAS CRÍTICOS

La mayor parte de las restricciones de unicidad e índices propuestos ya se implementaron en la migración `database/migrations/2026_04_29_100000_add_database_constraints_and_indexes.php`.

Implementado:
- `usuario.email` único
- `institucion.nombre` y `institucion.abrev` únicos
- `programa(cod, inst)` único
- `asignatura(programa, codigo, plan)` único
- `equiv(asg_pca, asg_ext)` único
- `plan(programa, num)` único
- `homologacion_detalle(homologacion_id, asignatura_pca_cod)` único
- índices críticos en `programa`, `asignatura`, `equiv`, `homologacion` y `usuario`

Pendiente:
- agregar foreign keys y constraints referenciales
- validar enums y reglas de negocio adicionales en `asignatura`
- revisar duplicados históricos antes de aplicar migraciones en ambientes existentes

---

### 2. Relaciones de Base de Datos Faltantes

#### Impacto
- Integridad referencial débil
- Posibles orfandades de datos
- Difícil mantener consistencia

#### Soluciones necesarias

```php
// 1. homologacion → usuario
Schema::table('homologacion', function (Blueprint $table) {
    $table->foreign('user_id')
        ->references('id')
        ->on('usuario')
        ->onDelete('restrict'); // No permitir borrar usuario con homologaciones
});

// 2. homologacion → programa (PCA)
Schema::table('homologacion', function (Blueprint $table) {
    $table->foreign('programa_pca_cod')
        ->references('cod')
        ->on('programa')
        ->onDelete('restrict');
});

// 3. homologacion → programa (externa)
Schema::table('homologacion', function (Blueprint $table) {
    $table->foreign('programa_ext_cod')
        ->references('cod')
        ->on('programa')
        ->onDelete('restrict');
});

// 4. homologacion → institucion
Schema::table('homologacion', function (Blueprint $table) {
    $table->foreign('institucion_id')
        ->references('id')
        ->on('institucion')
        ->onDelete('set null');
});

// 5. asignatura → programa
Schema::table('asignatura', function (Blueprint $table) {
    $table->foreign('programa')
        ->references('cod')
        ->on('programa')
        ->onDelete('restrict');
});
```

---

### 3. Validaciones Débiles en Controllers

Las Request classes ya están creadas para Usuario y Programa, por lo que las validaciones de unicidad y existencia de FK básicas están implementadas.

Pendiente:
- validar que el plan pertenece al programa al crear o editar una asignatura
- asegurar que todos los controladores usan `StoreUsuarioRequest`, `StoreProgramaRequest`, `StoreAsignaturaRequest` u otras Request classes específicas

---

---

## 🟠 PROBLEMAS ARQUITECTÓNICOS

### 4. Modelos Duplicados: User vs Usuario

**Problema:**
- Coexisten dos modelos de usuario
- `User` de Laravel no se usa
- `Usuario` es modelo legacy
- Confusión en el equipo de desarrollo

**Solución:**
```php
// Opción 1: Usar Usuario en toda la app (recomendado por compatibilidad)
// En app/Models/Usuario.php - extender Authenticatable correctamente

// Opción 2: Migrar a User y adaptar tablas
// Crear migración para renombrar tabla usuario → users
// Adaptar modelos y rutas
```

### 5. Validación de Datos Incompleta

Las Request classes ya existen para los flujos principales de Usuario, Programa y Asignatura. El foco actual es:
- asegurar el uso de estas clases en todos los controladores
- agregar validaciones específicas de relaciones (plan vs programa, nivel vs programa)
- completar validaciones de enums y reglas de negocio claras en los mensajes

---

### 6. SQL Injection y Seguridad

**Problemas detectados:**
```php
// ❌ Riesgoso: Query sin validación
DB::table('usuario')
    ->where('tipo', '!=', 1) // ← Asume tipo es seguro
    ->orderBy('usuario.id')

// ✅ Mejor: con validación
$tipo = (int) $request->get('tipo', 2);
DB::table('usuario')
    ->where('tipo', '!=', $tipo)
```

---

## 🟡 OPTIMIZACIONES RECOMENDADAS

### 7. Repository Pattern

**Problema:** Queries esparcidas en controllers

**Solución:**
```php
// app/Repositories/UsuarioRepository.php
class UsuarioRepository
{
    public function findById(string $id): ?Usuario { }
    public function findByEmail(string $email): ?Usuario { }
    public function findActivos(): Collection { }
    public function crearConAuditoria(array $data): Usuario { }
}

// En controller:
public function store(StoreUsuarioRequest $request, UsuarioRepository $repo)
{
    $usuario = $repo->crearConAuditoria($request->validated());
    return redirect()->route('admin.usuarios.index');
}
```

### 8. Soft Deletes

**Problema:** No hay borrado lógico, datos se pierden

**Solución:**
```php
// Agregar a tablas críticas
Schema::table('usuario', function (Blueprint $table) {
    $table->softDeletes();
});

Schema::table('programa', function (Blueprint $table) {
    $table->softDeletes();
});

Schema::table('asignatura', function (Blueprint $table) {
    $table->softDeletes();
});

// En modelos:
class Usuario extends Model
{
    use SoftDeletes;
}
```

### 9. Auditoría Mejorada

**Problema:** `audit_log` existe pero se usa inconsistentemente

**Solución:**
```php
// Agregar Observer para auto-auditar cambios
class UsuarioObserver
{
    public function created(Usuario $usuario)
    {
        AuditService::log('usuario.created', $usuario);
    }
    
    public function updated(Usuario $usuario)
    {
        AuditService::log('usuario.updated', [
            'before' => $usuario->getOriginal(),
            'after' => $usuario->getAttributes(),
        ]);
    }
}

// En AppServiceProvider:
Usuario::observe(UsuarioObserver::class);
```

### 10. Refactoring de Services

**Problema:** `AdminCatalogService` hace demasiado

**Solución - Dividir responsabilidades:**
```php
// Crear services específicos:
- ProgramaService (lógica de programas)
- AsignaturaService (lógica de asignaturas)
- EquivalenciaService (lógica de equivalencias)
- SignatureService (gestión de firmas)
- AuditService (ya existe, mejorar)
```

### 11. Índices de Base de Datos Faltantes

Los índices críticos para `homologacion`, `homologacion_detalle` y `usuario` ya se agregaron en la migración `database/migrations/2026_04_29_100000_add_database_constraints_and_indexes.php`.

---

---

## ✅ ACCIONES INMEDIATAS RECOMENDADAS

### Fase 1: Integridad de Datos (1-2 días)

- [x] Crear migración con constraints de unicidad
- [x] Crear migración con foreign keys
- [x] Validar integridad de datos con tests
- [x] Constraints aplicadas en BD test con éxito

**Archivos completados:**
- `database/migrations/2026_04_29_100000_add_database_constraints_and_indexes.php`
- `database/migrations/2026_04_30_050000_add_foreign_keys_to_saiq_schema.php`

### Fase 2: Validación de Datos (1 día)

- [x] Crear Request classes para Usuario, Programa y Asignatura
- [x] Agregar validación de existencia de FK
- [x] Agregar validaciones de relaciones de negocio (plan pertenece a programa)
- [x] Integrar uso de Request classes en controladores

**Archivos completados:**
- `app/Http/Requests/StoreUsuarioRequest.php` - validación de usuario único y programa existente
- `app/Http/Requests/StoreProgramaRequest.php` - validación de código único y nivel/institución existentes
- `app/Http/Requests/StoreAsignaturaRequest.php` - validación de código único por programa, plan existente y pertenencia de plan a programa
- `app/Http/Controllers/Admin/UsuarioController.php` - usa `StoreUsuarioRequest`
- `app/Http/Controllers/Admin/ProgramaController.php` - usa `StoreProgramaRequest`
- `app/Http/Controllers/Admin/AsignaturaController.php` - usa `StoreAsignaturaRequest`

### Fase 3: Refactoring Futuro (2-3 días)

- [ ] Crear Repository pattern (opcional)
- [ ] Dividir `AdminCatalogService` en servicios específicos (opcional)
- [ ] Agregar soft deletes (cuando se requiera historial completo)
- [ ] Implementar Observers para auditoría automática (cuando sea necesario)

### Fase 4: Testing (1 día)

- [x] Tests para validación de duplicados
- [x] Tests para integridad de constraints
- [ ] Tests adicionales para validación de relaciones (plan vs programa)
- [x] Suite actual: 99 tests, 281 assertions - PASANDO

---

## 📊 Puntos Positivos

✅ **Bien implementado:**
- ✅ Testing suite completa (99 tests, 281 assertions)
- ✅ Auditoría de acciones básica
- ✅ Seguridad de contraseñas mejorada
- ✅ Middleware de sesión personalizado
- ✅ Soporte para legacy MD5 hashes
- ✅ Documentación clara (TESTING_GUIDE.md)
- ✅ Rutas bien organizadas
- ✅ Models adecuados

---

## 🎯 Plan de Implementación

### Sprint 1: Crítico (Semana 1)
```
Lunes:   Crear migración de constraints
Martes:  Crear Request classes  
Miércoles: Agregar validaciones en controllers
Jueves:  Testing e iteración
Viernes: Deploy a testing
```

### Sprint 2: Mejoras (Semana 2)
```
Lunes:   Crear Repositories
Martes:  Agregar soft deletes
Miércoles: Observers de auditoría
Jueves:  Tests adicionales
Viernes: Code review y ajustes
```

---

## 📝 Scripts Útiles

### Validar Duplicados en BD Actual

```sql
-- Usuarios duplicados
SELECT id, COUNT(*) FROM usuario GROUP BY id HAVING COUNT(*) > 1;

-- Programas duplicados (mismo cod + inst)
SELECT cod, inst, COUNT(*) FROM programa 
GROUP BY cod, inst HAVING COUNT(*) > 1;

-- Asignaturas duplicadas
SELECT programa, codigo, plan, COUNT(*) FROM asignatura 
GROUP BY programa, codigo, plan HAVING COUNT(*) > 1;

-- Equivalencias duplicadas
SELECT asg_pca, asg_ext, COUNT(*) FROM equiv 
GROUP BY asg_pca, asg_ext HAVING COUNT(*) > 1;

-- Homologaciones con detalles duplicados
SELECT homologacion_id, asignatura_pca_cod, COUNT(*) FROM homologacion_detalle 
GROUP BY homologacion_id, asignatura_pca_cod HAVING COUNT(*) > 1;
```

### Limpiar Duplicados

```sql
-- Mantener solo el primero
DELETE FROM usuario WHERE id IN (
    SELECT id FROM (
        SELECT id, ROW_NUMBER() OVER (PARTITION BY id ORDER BY id) as rn 
        FROM usuario
    ) t WHERE rn > 1
);
```

---

## 🔗 Referencias

- Laravel Migrations: https://laravel.com/docs/9.x/migrations
- Eloquent Relationships: https://laravel.com/docs/9.x/eloquent-relationships
- Validation: https://laravel.com/docs/9.x/validation
- Repositories: https://laravel.com/docs/9.x/repositories (pattern)

---

## Contacto / Notas

- **Repo:** `c:\xampp\htdocs\SaiQ-Lavarel`
- **BD Testing:** `pcaedu_homologa_test`
- **BD Producción:** `pcaedu_homologa`
- **Último review:** 29/04/2026

