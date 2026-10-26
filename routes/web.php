<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SpeakerController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ResponsibleController;
use Illuminate\Support\Facades\Route;

// Página principal
Route::get('/', function () {
    return view('welcome');
});

// Dashboard
Route::get('/dashboard', function () {
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

// PANEL DE ADMINISTRACIÓN
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

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

        // proximamente
        // Asistencias
        // Certificados
        // Reportes
    });

// Rutas de autenticación
require __DIR__.'/auth.php';
