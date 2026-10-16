<?php

use App\Http\Controllers\Alumno\AgendaController;
use App\Http\Controllers\Alumno\CatalogoController;
use App\Http\Controllers\Alumno\InscripcionController;
use App\Http\Controllers\Alumno\PasesController;
use App\Http\Controllers\PaseLista\PaseListaController;
use App\Http\Controllers\Admin\ConstanciasPonentesController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    // Cada rol entra directo a su módulo (conservando mensajes como el de bienvenida).
    session()->reflash();
    if (auth()->user()->hasRole('alumno')) {
        return redirect()->route('alumno.catalogo');
    }

    if (auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
        return redirect()->route('admin.constancias');
    }

    if (auth()->user()->hasAnyRole(['tutor', 'docente', 'ponente'])) {
        return redirect()->route('pase-lista.index');
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

        Route::get('/pases', [PasesController::class, 'index'])->name('pases');
    });

// Pase de lista: el tutor/ponente escanea el QR de cada alumno
Route::middleware(['auth', 'role:tutor|docente|ponente|admin|super_admin'])
    ->prefix('pase-lista')
    ->name('pase-lista.')
    ->group(function () {
        Route::get('/', [PaseListaController::class, 'index'])->name('index');
        Route::get('/{activity}', [PaseListaController::class, 'show'])->name('show');
        Route::post('/{activity}/estado', [PaseListaController::class, 'toggle'])->name('toggle');
        Route::post('/{activity}/asistencias', [PaseListaController::class, 'store'])->name('store');
        Route::post('/{activity}/asistencias/sync', [PaseListaController::class, 'sync'])->name('sync');
        Route::post('/{activity}/manual/{enrollment}', [PaseListaController::class, 'manual'])
            ->whereNumber('enrollment')->name('manual');
    });

// Administración: el admin genera las constancias de los ponentes
Route::middleware(['auth', 'role:admin|super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/constancias-ponentes', [ConstanciasPonentesController::class, 'index'])->name('constancias');
        Route::get('/constancias-ponentes/{activity}/pdf', [ConstanciasPonentesController::class, 'download'])->name('constancias.pdf');
    });

require __DIR__.'/auth.php';
