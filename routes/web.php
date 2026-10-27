<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SpeakerController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResponsibleController;
use Illuminate\Support\Facades\Route;

// Página principal
Route::get('/', function () {
    return view('welcome');
});

// Dashboard: cada rol entra a su sección
Route::get('/dashboard', function () {
    // Administradores: al panel de administración
    if (auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
        return redirect()->route('admin.home');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Perfil del usuario
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

// PANEL DE ADMINISTRACIÓN (Administrador y Super Admin)
Route::middleware(['auth', 'role:admin|super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Panel General: entrada al panel de administración
        Route::get('/', [DashboardController::class, 'index'])
            ->name('home');

        // CRUD de Aulas
        Route::resource('rooms', RoomController::class)
            ->except(['show']);

        // CRUD de Ponentes
        Route::resource('speakers', SpeakerController::class)
            ->except(['show']);
        // CRUD de Actividades
        Route::resource('activities', ActivityController::class)
            ->except(['show']);

        // CRUD de Responsables (usuarios con rol "responsable")
        Route::resource('responsibles', ResponsibleController::class)
            ->except(['show']);

        // Asistencias: el admin consulta (el responsable pasa lista)
        Route::get('/asistencias', [AttendanceController::class, 'index'])
            ->name('attendances.index');
        Route::get('/asistencias/{activity}', [AttendanceController::class, 'show'])
            ->name('attendances.show');
        Route::get('/asistencias/{activity}/csv', [AttendanceController::class, 'export'])
            ->name('attendances.export');

        // proximamente
        // Certificados
        // Reportes
    });

// Rutas de autenticación
require __DIR__.'/auth.php';
