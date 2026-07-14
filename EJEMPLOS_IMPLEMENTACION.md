# 📝 Ejemplos de Implementación - Controllers Actualizados

## Introducción

Este documento muestra cómo actualizar los controllers existentes para usar las nuevas Request classes y mejorar la validación.

---

## 1. Usuario Controller

### Antes (Inseguro)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminCatalogService;
use App\Services\PasswordSecurityService;
use Illuminate\Http\Request;
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

    public function store(Request $request, AdminCatalogService $service, PasswordSecurityService $passwords)
    {
        // ❌ PROBLEMA 1: Validación manual
        $data = $request->validate([
            'no' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/'],
            'co' => ['required', 'string', 'min:6', 'max:100'],
            'pr' => ['required', 'string', 'max:10', 'not_in:0'],
            'fi' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        // ❌ PROBLEMA 2: Validación de duplicado DESPUÉS de validar
        if (DB::table('usuario')->where('id', $data['no'])->exists()) {
            return back()->withErrors(['no' => 'El usuario ya existe.'])->withInput();
        }

        // ❌ PROBLEMA 3: Sin validar que programa existe
        // ❌ PROBLEMA 4: Sin validar tipos enum

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

### Después (Seguro)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;  // ← NUEVO
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

    // ✅ CAMBIO 1: Usar StoreUsuarioRequest
    public function store(
        StoreUsuarioRequest $request,
        AdminCatalogService $service,
        PasswordSecurityService $passwords
    ) {
        // ✅ CAMBIO 2: Validación ya hecha por Request class
        $data = $request->validated();

        // ✅ CAMBIO 3: Ya validado que programa existe
        // ✅ CAMBIO 4: Ya validado que usuario no existe
        // ✅ CAMBIO 5: Ya validado formato de usuario

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

**Cambios principales:**
- ✅ Importar `StoreUsuarioRequest`
- ✅ Cambiar tipo de parámetro `Request $request` → `StoreUsuarioRequest $request`
- ✅ Usar `$request->validated()` en lugar de `$request->validate()`
- ✅ Eliminar validación manual
- ✅ Confiar en Request class para FK validation

---

## 2. Programa Controller

### Antes (Con problemas)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramaController extends Controller
{
    public function index()
    {
        return view('admin.programas.index', [
            'programas' => DB::table('programa')
                ->leftJoin('nivel', 'nivel.id', '=', 'programa.nivel')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('programa.nombre')
                ->select('programa.*', 'nivel.descripcion as nivel_nombre', 'institucion.nombre as institucion_nombre')
                ->get(),
            'instituciones' => DB::table('institucion')->orderBy('nombre')->get(),
            'niveles' => DB::table('nivel')->orderBy('descripcion')->get(),
        ]);
    }

    public function store(Request $request, AdminCatalogService $service)
    {
        // ❌ Validación manual sin FK check
        $data = $request->validate([
            'co' => ['nullable', 'string', 'max:10'],
            'no' => ['required', 'string', 'max:200'],
            'ni' => ['required', 'integer', 'min:1'],
            'pr' => ['required', 'integer', 'min:1'],
            'ac' => ['required', 'integer', 'in:0,1'],
        ]);

        // ❌ Sin validar que 'ni' (nivel) existe
        // ❌ Sin validar que 'pr' (institución) existe
        // ❌ Sin validar unicidad de (cod, inst)

        $isPca = (int) $data['pr'] === 1;
        $code = $isPca ? strtoupper((string) $data['co']) : $service->nextProgramCode((int) $data['pr']);

        if ($isPca && strlen($code) !== 3) {
            return back()->withErrors(['co' => 'El programa PCA debe tener codigo de tres cifras.'])->withInput();
        }

        if (DB::table('programa')->where('cod', $code)->exists()) {
            return back()->withErrors(['co' => 'El codigo del programa ya existe.'])->withInput();
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
}
```

### Después (Mejorado)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramaRequest;  // ← NUEVO
use App\Services\AdminCatalogService;
use Illuminate\Support\Facades\DB;

class ProgramaController extends Controller
{
    public function index()
    {
        return view('admin.programas.index', [
            'programas' => DB::table('programa')
                ->leftJoin('nivel', 'nivel.id', '=', 'programa.nivel')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('programa.nombre')
                ->select('programa.*', 'nivel.descripcion as nivel_nombre', 'institucion.nombre as institucion_nombre')
                ->get(),
            'instituciones' => DB::table('institucion')->orderBy('nombre')->get(),
            'niveles' => DB::table('nivel')->orderBy('descripcion')->get(),
        ]);
    }

    // ✅ CAMBIO 1: Usar StoreProgramaRequest
    public function store(StoreProgramaRequest $request, AdminCatalogService $service)
    {
        // ✅ CAMBIO 2: Todas las validaciones hechas por Request class
        $data = $request->validated();

        // ✅ En este punto, sabemos que:
        //    - 'no' (nombre) es requerido y válido
        //    - 'ni' (nivel) existe en tabla nivel
        //    - 'pr' (institución) existe en tabla institucion
        //    - 'ac' (activo) es 0 o 1
        //    - 'co' (código) no existe en tabla programa

        $isPca = (int) $data['pr'] === 1;
        $code = $isPca ? strtoupper((string) $data['co']) : $service->nextProgramCode((int) $data['pr']);

        // ✅ CAMBIO 3: Validación de formato PCA aún necesaria
        if ($isPca && strlen($code) !== 3) {
            return back()
                ->withErrors(['co' => 'El programa PCA debe tener codigo de tres cifras.'])
                ->withInput();
        }

        // ✅ CAMBIO 4: Ya no necesita validar unicidad (Request class lo hizo)

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
}
```

---

## 3. Asignatura Controller

### Después (Mejorado)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAsignaturaRequest;  // ← NUEVO
use App\Services\AdminCatalogService;
use Illuminate\Support\Facades\DB;

class AsignaturaController extends Controller
{
    public function index()
    {
        return view('admin.asignaturas.index', [
            'asignaturas' => DB::table('asignatura')
                ->leftJoin('programa', 'programa.cod', '=', 'asignatura.programa')
                ->leftJoin('plan', 'plan.id', '=', 'asignatura.plan')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('asignatura.nombre')
                ->select(
                    'asignatura.*',
                    'programa.nombre as programa_nombre',
                    'plan.num as plan_num',
                    'institucion.nombre as institucion_nombre'
                )
                ->get(),
            'programas' => DB::table('programa')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('programa.nombre')
                ->select('programa.*', 'institucion.nombre as institucion_nombre')
                ->get(),
            'planes' => DB::table('plan')->orderBy('num')->get(),
        ]);
    }

    public function store(StoreAsignaturaRequest $request, AdminCatalogService $service)
    {
        // ✅ Validación ya hecha
        $data = $request->validated();

        // ✅ En este punto, sabemos que:
        //    - Programa existe
        //    - Si plan se proporciona, debería existir
        //    - Créditos >= 0

        $program = DB::table('programa')->where('cod', $data['pr'])->first();
        
        // ✅ Validación adicional: plan pertenece al programa
        if ($data['pl']) {
            $plan = DB::table('plan')->where('id', $data['pl'])->first();
            if (!$plan || $plan->programa !== $program->cod) {
                return back()
                    ->withErrors(['pl' => 'El plan no pertenece a este programa.'])
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
}
```

---

## 4. Institución Controller

### Después (Mejorado)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstitucionRequest;  // ← NUEVO
use App\Services\AdminCatalogService;
use Illuminate\Support\Facades\DB;

class InstitucionController extends Controller
{
    public function index()
    {
        return view('admin.instituciones.index', [
            'instituciones' => DB::table('institucion')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(StoreInstitucionRequest $request, AdminCatalogService $service)
    {
        // ✅ Todas las validaciones hechas
        $data = $request->validated();

        // ✅ En este punto:
        //    - Nombre es único
        //    - Abreviatura es única (si se proporciona)
        //    - Tipo es válido

        DB::table('institucion')->insert([
            'nombre' => $data['no'],
            'abrev' => $data['ab'] ?? null,
            'tipo' => $data['ti'] ?? null,
            'ciudad' => $data['ci'] ?? null,
            'pais' => $data['pa'] ?? null,
            'activo' => $data['ac'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service->audit('CREATE_INSTITUTION', [
            'nombre' => $data['no'],
            'abrev' => $data['ab'] ?? 'N/A',
        ]);

        return redirect()->route('admin.instituciones.index')
            ->with('success', 'Institución creada exitosamente.');
    }
}
```

---

## 5. Equivalencia Controller

### Después (Mejorado)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquivalenciaRequest;  // ← NUEVO
use App\Services\AdminCatalogService;
use Illuminate\Support\Facades\DB;

class EquivalenciaController extends Controller
{
    public function index()
    {
        return view('admin.equivalencias.index', [
            'equivalencias' => DB::table('equiv')
                ->leftJoin('asignatura as asg_pca', 'asg_pca.cod', '=', 'equiv.asg_pca')
                ->leftJoin('asignatura as asg_ext', 'asg_ext.cod', '=', 'equiv.asg_ext')
                ->orderBy('equiv.asg_pca')
                ->select(
                    'equiv.id',
                    'equiv.asg_pca',
                    'equiv.asg_ext',
                    'asg_pca.nombre as asg_pca_nombre',
                    'asg_ext.nombre as asg_ext_nombre'
                )
                ->get(),
            'asignaturas' => DB::table('asignatura')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(StoreEquivalenciaRequest $request, AdminCatalogService $service)
    {
        // ✅ Todas las validaciones hechas
        $data = $request->validated();

        // ✅ En este punto:
        //    - Ambas asignaturas existen
        //    - No son la misma asignatura
        //    - Será único en la BD

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
}
```

---

## 📊 Comparativa Antes/Después

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Ubicación validación** | Controller | Request class |
| **Duplicado de código** | Sí (5 controllers) | No (centralizado) |
| **Validación FK** | Manual o inexistente | Automática |
| **Consistencia mensajes** | Inconsistente | Consistente |
| **Testing** | Difícil | Fácil |
| **Líneas de código** | 40-50 validación | 5-10 en controller |
| **Mantenibilidad** | Baja | Alta |

---

## ✅ Checklist de Implementación

- [ ] Crear todas las Request classes
- [ ] Actualizar Usuario controller
- [ ] Actualizar Programa controller
- [ ] Actualizar Asignatura controller
- [ ] Actualizar Institución controller
- [ ] Actualizar Equivalencia controller
- [ ] Tests para validaciones
- [ ] Verificar mensajes de error en vistas
- [ ] Documentación actualizada

---

## 🧪 Testing de Cambios

```bash
# Ejecutar tests específicos
php artisan test tests/Feature/Admin/UsuarioValidationTest.php

# Ver qué cambió
git diff app/Http/Controllers/Admin/

# Revisar Request classes
git diff app/Http/Requests/
```

---

**Última actualización:** 29/04/2026
