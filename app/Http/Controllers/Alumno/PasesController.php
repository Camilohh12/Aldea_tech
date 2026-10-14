<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Mis pases": el QR único de cada inscripción, para que el tutor lo escanee. */
class PasesController extends Controller
{
    public function index(Request $request): View
    {
        $pases = Enrollment::registered()
            ->where('user_id', $request->user()->id)
            ->with(['activity.type', 'activity.room'])
            ->get()
            // Primero las que siguen vigentes (en curso o próximas), luego las terminadas.
            ->sortBy(fn (Enrollment $e) => [
                $e->activity->hasEnded() ? 1 : 0,
                $e->activity->startsAt()?->timestamp ?? PHP_INT_MAX,
            ])
            ->values();

        return view('alumno.pases', ['pases' => $pases]);
    }
}
