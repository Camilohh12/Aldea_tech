<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\EnrollmentException;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function __construct(private EnrollmentService $service)
    {
    }

    public function store(Request $request, Activity $activity): RedirectResponse
    {
        try {
            $this->service->enroll($request->user(), $activity->id);
        } catch (EnrollmentException $e) {
            return back()->with('error', $e->getMessage())->with('error_title', $e->title());
        }

        return back()->with('success', "¡Listo! Te inscribiste a «{$activity->title}».");
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        try {
            $this->service->cancel($request->user(), $activity->id);
        } catch (EnrollmentException $e) {
            return back()->with('error', $e->getMessage())->with('error_title', $e->title());
        }

        return back()->with('success', "Cancelaste tu lugar en «{$activity->title}».");
    }
}
