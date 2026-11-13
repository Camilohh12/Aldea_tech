<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\ReglasAlumno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Al cambiar de grupo pasa a la lista del tutor de ese grupo
        if ($request->esAlumno() && $request->user()->isDirty('grupo')
            && ($tutor = \App\Support\Grupo::tutorDe($request->user()->grupo))) {
            $request->user()->tutor_id = $tutor;
        }

        // Al cambiar el cuatrimestre o el grupo se actualiza si el alumno es regular o irregular
        if ($request->esAlumno() && $request->user()->isDirty(['cuatrimestre', 'grupo'])) {
            $request->user()->estatus = ReglasAlumno::estatus($request->user()->cuatrimestre, $request->user()->grupo);
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
