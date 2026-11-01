<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
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
use App\Http\Controllers\Tutor\TutoradosController;
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

    // Tutores: a la supervisión de sus tutorados
    if ($user->hasRole('tutor')) {
        return redirect()->route('tutor.tutorados');
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

// MÓDULO TUTOR: supervisión de tutorados (solo lectura)
Route::middleware(['auth', 'role:tutor'])
    ->prefix('tutor')
    ->name('tutor.')
    ->group(function () {
        Route::get('/tutorados', [TutoradosController::class, 'index'])->name('tutorados');
        Route::get('/tutorados/{alumno}', [TutoradosController::class, 'show'])
            ->whereNumber('alumno')->name('tutorados.show');
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

        // CRUD de Administradores (cuentas con acceso al panel)
        Route::resource('administrators', AdministratorController::class)
            ->except(['show']);

        // Asistencias: el admin consulta (el responsable pasa lista)
        Route::get('/asistencias', [AttendanceController::class, 'index'])
            ->name('attendances.index');
        Route::get('/asistencias/{activity}', [AttendanceController::class, 'show'])
            ->name('attendances.show');
        Route::get('/asistencias/{activity}/csv', [AttendanceController::class, 'export'])
            ->name('attendances.export');

        // Certificados de ponentes: lista, plantilla editable y PDF
        Route::get('/certificados', [CertificateController::class, 'index'])
            ->name('certificates.index');
        Route::get('/certificados/plantilla', [CertificateController::class, 'editTemplate'])
            ->name('certificates.template');
        Route::put('/certificados/plantilla', [CertificateController::class, 'updateTemplate'])
            ->name('certificates.template.update');
        Route::get('/certificados/plantilla/muestra', [CertificateController::class, 'sample'])
            ->name('certificates.sample');
        Route::get('/certificados/{activity}/{speaker}/pdf', [CertificateController::class, 'download'])
            ->name('certificates.download');

        // Reportes: se ven en pantalla y se descargan en CSV
        Route::get('/reportes', [ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('/reportes/{reporte}/csv', [ReportController::class, 'export'])
            ->name('reports.export');
    });

// Rutas de autenticación
require __DIR__.'/auth.php';
