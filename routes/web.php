<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ConstanciasPonentesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResponsibleController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SpeakerController;
use App\Http\Controllers\Alumno\AgendaController;
use App\Http\Controllers\Alumno\CatalogoController;
use App\Http\Controllers\Alumno\InscripcionController;
use App\Http\Controllers\Alumno\PasesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaseLista\PaseListaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Página principal: portada del evento y catálogo público
Route::get('/', [HomeController::class, 'index'])->name('home');

// Dashboard: cada rol entra a su sección
Route::get('/dashboard', function () {
    // Conserva mensajes de una redirección previa (p. ej. la bienvenida del registro)
    session()->reflash();

    $user = auth()->user();

    // Administradores: al panel de administración
    if ($user->hasAnyRole(['admin', 'super_admin'])) {
        return redirect()->route('admin.home');
    }

    if ($user->hasRole('alumno')) {
        return redirect()->route('alumno.catalogo');
    }

    // Responsables y docentes: a pasar lista de sus actividades
    if ($user->hasAnyRole(['responsable', 'docente'])) {
        return redirect()->route('pase-lista.index');
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

// MÓDULO ALUMNO: catálogo, inscripción, agenda y pases QR
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

// PASE DE LISTA: solo el responsable asignado a la actividad y los administradores
// (la regla por actividad está en Activity::canTakeAttendance)
Route::middleware(['auth', 'role:responsable|docente|admin|super_admin'])
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

        // Certificados: constancias de ponentes en PDF
        Route::get('/constancias-ponentes', [ConstanciasPonentesController::class, 'index'])
            ->name('constancias');
        Route::get('/constancias-ponentes/{activity}/{speaker}/pdf', [ConstanciasPonentesController::class, 'download'])
            ->name('constancias.pdf');

        // proximamente
        // Asistencias
        // Reportes
    });

// Rutas de autenticación
require __DIR__.'/auth.php';
