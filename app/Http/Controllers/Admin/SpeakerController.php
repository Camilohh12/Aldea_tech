<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LeeFiltros;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpeakerController extends Controller
{
    use LeeFiltros;

    // Mostrar todos los ponentes (con búsqueda por nombre o matrícula)
    public function index(Request $request)
    {
        $search = $this->filtroTexto($request, 'q');

        $speakers = Speaker::withCount('activities')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('matricula_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Datos para las tarjetas de resumen
        $totalSpeakers = Speaker::count();
        $speakersWithActivities = Speaker::has('activities')->count();

        return view('admin.speakers.index', compact(
            'speakers', 'search', 'totalSpeakers', 'speakersWithActivities'
        ));
    }

    // Mostrar formulario para registrar ponente
    public function create()
    {
        return view('admin.speakers.create');
    }

    // Guardar ponente nuevo
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        Speaker::create($validated);

        return redirect()
            ->route('admin.speakers.index')
            ->with('success', 'Ponente registrado correctamente.');
    }

    // Mostrar formulario para editar
    public function edit(Speaker $speaker)
    {
        // Actividades asignadas, para mostrarlas debajo del formulario
        $activities = $speaker->activities()
            ->with('room')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('admin.speakers.edit', compact('speaker', 'activities'));
    }

    // Actualizar información del ponente
    public function update(Request $request, Speaker $speaker)
    {
        $validated = $request->validate($this->rules($speaker), $this->messages());

        $speaker->update($validated);

        return redirect()
            ->route('admin.speakers.index')
            ->with('success', 'Ponente actualizado correctamente.');
    }

    // Eliminar ponente
    public function destroy(Speaker $speaker)
    {
        // Revisa la tabla activity_speaker y también la columna
        // activities.speaker_id, que borraría las actividades en cascada
        $hasActivities = $speaker->activities()->exists()
            || Activity::where('speaker_id', $speaker->id)->exists();

        if ($hasActivities) {
            return redirect()
                ->route('admin.speakers.index')
                ->with('error', "No se puede eliminar a «{$speaker->name}» porque tiene actividades asignadas.");
        }

        $speaker->delete();

        return redirect()
            ->route('admin.speakers.index')
            ->with('success', 'Ponente eliminado correctamente.');
    }

    // Reglas de validación (al editar, ignora la matrícula del mismo ponente)
    private function rules(?Speaker $speaker = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'matricula_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('speakers', 'matricula_id')->ignore($speaker?->id),
            ],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }

    // Mensajes de validación en español
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre del ponente es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'matricula_id.max' => 'La matrícula no puede tener más de 50 caracteres.',
            'matricula_id.unique' => 'Ya existe un ponente con esa matrícula.',
            'bio.max' => 'La especialidad o semblanza no puede tener más de 2,000 caracteres.',
        ];
    }
}
