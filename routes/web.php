<?php

use App\Http\Controllers\Alumno\AgendaController;
use App\Http\Controllers\Alumno\AsistenciaController;
use App\Http\Controllers\Alumno\CatalogoController;
use App\Http\Controllers\Alumno\InscripcionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    // El alumno entra directo a su módulo.
    if (auth()->user()->hasRole('alumno')) {
        return redirect()->route('alumno.catalogo');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Módulo Alumno (issue: interfaz móvil del alumno)
Route::middleware(['auth', 'role:alumno'])
    ->prefix('alumno')
    ->name('alumno.')
    ->group(function () {
        Route::get('/actividades', [CatalogoController::class, 'index'])->name('catalogo');
        Route::post('/actividades/{activity}/inscripcion', [InscripcionController::class, 'store'])->name('inscripcion.store');
        Route::delete('/actividades/{activity}/inscripcion', [InscripcionController::class, 'destroy'])->name('inscripcion.destroy');

        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda');

        Route::get('/escaner', [AsistenciaController::class, 'escaner'])->name('escaner');
        Route::post('/asistencias', [AsistenciaController::class, 'store'])->name('asistencias.store');
        Route::post('/asistencias/sync', [AsistenciaController::class, 'sync'])->name('asistencias.sync');
    });

require __DIR__.'/auth.php';
