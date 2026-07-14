<?php

use App\Http\Controllers\Admin\InstitucionController;
use App\Http\Controllers\Admin\AsignaturaController;
use App\Http\Controllers\Admin\EquivalenciaController;
use App\Http\Controllers\Admin\ProgramaController;
use App\Http\Controllers\Admin\RelacionController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomologacionBorradorController;
use App\Http\Controllers\HomologacionAsignaturaController;
use App\Http\Controllers\HomologacionProgramaController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ReconocimientoTituloController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => session()->has('x') ? redirect()->route('dashboard') : redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('saiq.auth')->group(function () {
    Route::get('/inicio', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/homologaciones/borradores', [HomologacionBorradorController::class, 'index'])
        ->name('homologaciones.borradores.index');
    Route::get('/homologaciones/borradores/{homologacion}/editar', [HomologacionBorradorController::class, 'edit'])
        ->whereNumber('homologacion')
        ->name('homologaciones.borradores.edit');
    Route::get('/homologaciones/{homologacion}/trazabilidad', [HomologacionBorradorController::class, 'show'])
        ->whereNumber('homologacion')
        ->name('homologaciones.trazabilidad.show');

    Route::get('/homologaciones/programa', [HomologacionProgramaController::class, 'create'])
        ->name('homologaciones.programa.create');
    Route::post('/homologaciones/programa', [HomologacionProgramaController::class, 'generate'])
        ->name('homologaciones.programa.generate');

    Route::get('/homologaciones/asignatura', [HomologacionAsignaturaController::class, 'create'])
        ->name('homologaciones.asignatura.create');
    Route::post('/homologaciones/asignatura/revision', [HomologacionAsignaturaController::class, 'review'])
        ->name('homologaciones.asignatura.review');
    Route::post('/homologaciones/asignatura/resultado', [HomologacionAsignaturaController::class, 'result'])
        ->name('homologaciones.asignatura.result');

    Route::get('/reconocimiento-titulo', [ReconocimientoTituloController::class, 'create'])
        ->name('reconocimiento.create');
    Route::post('/reconocimiento-titulo', [ReconocimientoTituloController::class, 'generate'])
        ->name('reconocimiento.generate');

    Route::get('/clave', [PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/clave', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('saiq.admin')->group(function () {
        Route::get('/admin/instituciones', [InstitucionController::class, 'index'])->name('admin.instituciones.index');
        Route::post('/admin/instituciones', [InstitucionController::class, 'store'])->name('admin.instituciones.store');
        Route::redirect('/programa.php', '/admin/programas');
        Route::redirect('/programas.php', '/admin/programas');
        Route::get('/admin/programas', [ProgramaController::class, 'index'])->name('admin.programas.index');
        Route::post('/admin/programas', [ProgramaController::class, 'store'])->name('admin.programas.store');
        Route::get('/admin/asignaturas', [AsignaturaController::class, 'index'])->name('admin.asignaturas.index');
        Route::post('/admin/asignaturas', [AsignaturaController::class, 'store'])->name('admin.asignaturas.store');
        Route::get('/admin/equivalencias', [EquivalenciaController::class, 'index'])->name('admin.equivalencias.index');
        Route::post('/admin/equivalencias', [EquivalenciaController::class, 'store'])->name('admin.equivalencias.store');
        Route::get('/admin/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios.index');
        Route::post('/admin/usuarios', [UsuarioController::class, 'store'])->name('admin.usuarios.store');
        Route::get('/admin/relaciones', [RelacionController::class, 'index'])->name('admin.relaciones.index');
        Route::post('/admin/relaciones', [RelacionController::class, 'store'])->name('admin.relaciones.store');
    });

    Route::get('/migracion/pendiente/{module}', function (string $module) {
        return view('migracion.pending', ['module' => $module]);
    })->name('migracion.pending');
});
