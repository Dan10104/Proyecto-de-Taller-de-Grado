<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
Route::middleware(['auth', 'access.session'])->group(function () {
    Route::get('/inicio', fn () => Inertia::render('Home'))->name('inicio');
    Route::get('/usuarios', [UserController::class, 'index'])->middleware('can:usuarios.ver')->name('usuarios.index');
    Route::get('/usuarios/crear', [UserController::class, 'create'])->middleware('can:usuarios.crear')->name('usuarios.create');
    Route::post('/usuarios', [UserController::class, 'store'])->middleware('can:usuarios.crear')->name('usuarios.store');
    Route::get('/usuarios/{usuario}', [UserController::class, 'show'])->middleware('can:usuarios.ver')->name('usuarios.show');
    Route::get('/usuarios/{usuario}/editar', [UserController::class, 'edit'])->middleware('can:usuarios.editar')->name('usuarios.edit');
    Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->middleware('can:usuarios.editar')->name('usuarios.update');
    Route::patch('/usuarios/{usuario}/deshabilitar', [UserController::class, 'disable'])->middleware('can:usuarios.deshabilitar')->name('usuarios.disable');
    Route::get('/roles', [RoleController::class, 'index'])->middleware('can:roles.ver')->name('roles.index');
    Route::get('/roles/crear', [RoleController::class, 'create'])->middleware('can:roles.crear')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('can:roles.crear')->name('roles.store');
    Route::get('/roles/{rol}', [RoleController::class, 'show'])->middleware('can:roles.ver')->name('roles.show');
    Route::get('/roles/{rol}/editar', [RoleController::class, 'edit'])->middleware('can:roles.editar')->name('roles.edit');
    Route::put('/roles/{rol}', [RoleController::class, 'update'])->middleware('can:roles.editar')->name('roles.update');
    Route::put('/roles/{rol}/permisos', [RoleController::class, 'permissions'])->middleware('can:roles.gestionar_permisos')->name('roles.permissions');
    Route::get('/permisos', [RoleController::class, 'catalog'])->middleware('can:permisos.ver')->name('permisos.index');
    Route::get('/asignaciones/candidatos', [RoleController::class, 'candidates'])->middleware('can:roles.asignar')->name('asignaciones.candidates');
    Route::post('/roles/{rol}/asignaciones', [RoleController::class, 'assign'])->middleware('can:roles.asignar')->name('asignaciones.store');
    Route::delete('/roles/{rol}/asignaciones', [RoleController::class, 'withdraw'])->middleware('can:roles.retirar')->name('asignaciones.destroy');
});
