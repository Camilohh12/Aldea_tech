<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    // Mostrar todas las aulas
    public function index()
    {
        $rooms = Room::withCount('activities')
            ->orderBy('name')
            ->paginate(10);

        // Datos para las tarjetas de resumen
        $totalRooms = Room::count();
        $totalCapacity = (int) Room::sum('capacity');
        $roomsInUse = Room::has('activities')->count();

        return view('admin.rooms.index', compact(
            'rooms', 'totalRooms', 'totalCapacity', 'roomsInUse'
        ));
    }

    // Mostrar formulario para crear aula
    public function create()
    {
        return view('admin.rooms.create');
    }

    // Guardar aula nueva
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                'unique:rooms,name'
            ],
            'capacity' => [
                'required', 'integer', 'min:1', 'max:10000'
            ],
        ], $this->messages());

        Room::create($validated);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Aula registrada correctamente.');
    }

    // Mostrar formulario para editar
    public function edit(Room $room)
    {
        // Cupo más alto de las actividades del aula (0 si no tiene)
        $minCapacity = (int) $room->activities()->max('capacity');

        return view('admin.rooms.edit', compact('room', 'minCapacity'));
    }

    // Actualizar información del aula
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'name')->ignore($room->id)
            ],
            'capacity' => [
                'required', 'integer', 'min:1', 'max:10000'
            ],
        ], $this->messages());

        // Cupo más alto entre las actividades de esta aula (0 si no tiene)
        $maxCapacity = (int) $room->activities()
            ->max('capacity');

        if ($validated['capacity'] < $maxCapacity) {
            return back()
                ->withInput()
                ->withErrors([
                    'capacity' => "No puedes reducir la capacidad a menos de {$maxCapacity}: hay actividades en esta aula con ese cupo."
                ]);
        }

        $room->update($validated);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Aula actualizada correctamente.');
    }

    // Eliminar aula
    public function destroy(Room $room)
    {
        if ($room->activities()->exists()) {
            return redirect()
                ->route('admin.rooms.index')
                ->with('error', "No se puede eliminar «{$room->name}» porque tiene actividades asignadas.");
        }

        $room->delete();

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Aula eliminada correctamente.');
    }

    // Mensajes de validación en español
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre del aula es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe un aula con ese nombre.',
            'capacity.required' => 'La capacidad es obligatoria.',
            'capacity.integer' => 'La capacidad debe ser un número entero.',
            'capacity.min' => 'La capacidad debe ser de al menos 1 persona.',
            'capacity.max' => 'La capacidad no puede ser mayor a 10,000 personas.',
        ];
    }
}
