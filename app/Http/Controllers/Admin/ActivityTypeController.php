<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityTypeController extends Controller
{
    // Mostrar todos los tipos de actividad
    public function index()
    {
        $types = ActivityType::withCount('activities')
            ->orderBy('name')
            ->paginate(10);

        // Datos para las tarjetas de resumen
        $totalTypes = ActivityType::count();
        $typesInUse = ActivityType::has('activities')->count();

        return view('admin.activity-types.index', compact(
            'types', 'totalTypes', 'typesInUse'
        ));
    }

    // Mostrar formulario para crear tipo
    public function create()
    {
        return view('admin.activity-types.create');
    }

    // Guardar tipo nuevo
    public function store(Request $request)
    {
        ActivityType::create($request->validate($this->rules(), $this->messages()));

        return redirect()
            ->route('admin.activity-types.index')
            ->with('success', 'Tipo de actividad registrado correctamente.');
    }

    // Mostrar formulario para editar
    public function edit(ActivityType $activityType)
    {
        $activitiesCount = $activityType->activities()->count();

        return view('admin.activity-types.edit', compact('activityType', 'activitiesCount'));
    }

    // Actualizar información del tipo
    public function update(Request $request, ActivityType $activityType)
    {
        $activityType->update($request->validate($this->rules($activityType), $this->messages()));

        return redirect()
            ->route('admin.activity-types.index')
            ->with('success', 'Tipo de actividad actualizado correctamente.');
    }

    // Eliminar tipo
    public function destroy(ActivityType $activityType)
    {
        // En la base de datos, borrar un tipo borraría también sus actividades e inscripciones
        if ($activityType->activities()->exists()) {
            return redirect()
                ->route('admin.activity-types.index')
                ->with('error', "No se puede eliminar «{$activityType->name}» porque tiene actividades asignadas.");
        }

        $activityType->delete();

        return redirect()
            ->route('admin.activity-types.index')
            ->with('success', 'Tipo de actividad eliminado correctamente.');
    }

    // Reglas de validación (al editar, el nombre actual no cuenta como repetido)
    private function rules(?ActivityType $activityType = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('activity_types', 'name')->ignore($activityType?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    // Mensajes de validación en español
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre del tipo es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 60 caracteres.',
            'name.unique' => 'Ya existe un tipo de actividad con ese nombre.',
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',
        ];
    }
}
