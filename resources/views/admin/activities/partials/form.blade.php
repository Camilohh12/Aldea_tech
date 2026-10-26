{{-- Campos de la actividad, compartidos por create y edit.
     Recibe $activity (null al registrar), $types, $rooms, $speakers,
     $responsibles y $eventDays. --}}
@php
    $field = 'w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container';
    $border = fn ($name) => $errors->has($name) ? 'border-red-400' : 'border-gray-300';

    $selectedSpeakers = collect(old('speakers', $activity?->speakers->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    // Primero los responsables; los docentes también pueden pasar lista
    $responsibleGroups = [
        'Responsables' => $responsibles->filter(fn ($user) => $user->hasRole('responsable')),
        'Docentes (también pueden pasar lista)' => $responsibles->reject(fn ($user) => $user->hasRole('responsable')),
    ];
@endphp

@if ($errors->any())
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl mb-8">
        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <span>No se guardó la actividad. Revisa los campos marcados en rojo.</span>
    </div>
@endif

<!-- 01. Información general -->
<section class="mb-10">

    <h2 class="font-heading text-lg font-bold text-upp-primary pb-3 mb-6 border-b border-upp-surface-high">
        01. Información general
    </h2>

    <div class="grid gap-6 md:grid-cols-3">

        <div class="md:col-span-2">
            <label for="title" class="block text-sm font-semibold text-upp-primary mb-2">
                Nombre de la actividad <span class="text-red-600">*</span>
            </label>
            <input type="text" id="title" name="title" maxlength="255" required
                   value="{{ old('title', $activity?->title) }}"
                   placeholder="Ej. Taller de Ciberseguridad Defensiva"
                   class="{{ $field }} {{ $border('title') }}">
            @error('title')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="activity_type_id" class="block text-sm font-semibold text-upp-primary mb-2">
                Tipo <span class="text-red-600">*</span>
            </label>
            <select id="activity_type_id" name="activity_type_id" required class="{{ $field }} {{ $border('activity_type_id') }}">
                <option value="">Seleccionar tipo</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(old('activity_type_id', $activity?->activity_type_id) == $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
            @error('activity_type_id')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="md:col-span-3">
            <label for="description" class="block text-sm font-semibold text-upp-primary mb-2">
                Descripción <span class="font-normal text-gray-500">(opcional)</span>
            </label>
            <textarea id="description" name="description" rows="3" maxlength="5000"
                      placeholder="¿De qué trata la actividad y qué aprenderán los participantes?"
                      class="{{ $field }} {{ $border('description') }}">{{ old('description', $activity?->description) }}</textarea>
            @error('description')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

    </div>

</section>

<!-- 02. Lugar y horario -->
<section class="mb-10">

    <h2 class="font-heading text-lg font-bold text-upp-primary pb-3 mb-6 border-b border-upp-surface-high">
        02. Lugar y horario
    </h2>

    <div class="grid gap-6 md:grid-cols-2">

        <div>
            <label for="room_id" class="block text-sm font-semibold text-upp-primary mb-2">
                Aula <span class="text-red-600">*</span>
            </label>
            <select id="room_id" name="room_id" required class="{{ $field }} {{ $border('room_id') }}">
                <option value="">Seleccionar aula</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" data-capacity="{{ $room->capacity }}"
                            @selected(old('room_id', $activity?->room_id) == $room->id)>
                        {{ $room->name }} ({{ $room->capacity }} {{ $room->capacity === 1 ? 'persona' : 'personas' }})
                    </option>
                @endforeach
            </select>
            @error('room_id')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="date" class="block text-sm font-semibold text-upp-primary mb-2">
                Día del evento <span class="text-red-600">*</span>
            </label>
            <select id="date" name="date" required class="{{ $field }} {{ $border('date') }}">
                <option value="">Seleccionar día</option>
                @foreach ($eventDays as $value => $label)
                    <option value="{{ $value }}" @selected(old('date', $activity?->date?->format('Y-m-d')) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('date')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="start_time" class="block text-sm font-semibold text-upp-primary mb-2">
                Hora de inicio <span class="text-red-600">*</span>
            </label>
            <input type="time" id="start_time" name="start_time" required
                   value="{{ old('start_time', $activity ? substr($activity->start_time, 0, 5) : null) }}"
                   class="{{ $field }} {{ $border('start_time') }}">
            @error('start_time')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="end_time" class="block text-sm font-semibold text-upp-primary mb-2">
                Hora de finalización <span class="text-red-600">*</span>
            </label>
            <input type="time" id="end_time" name="end_time" required
                   value="{{ old('end_time', $activity ? substr($activity->end_time, 0, 5) : null) }}"
                   class="{{ $field }} {{ $border('end_time') }}">
            @error('end_time')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="capacity" class="block text-sm font-semibold text-upp-primary mb-2">
                Cupo máximo <span class="text-red-600">*</span>
            </label>
            <input type="number" id="capacity" name="capacity" min="1" required
                   value="{{ old('capacity', $activity?->capacity) }}"
                   placeholder="Ej. 30"
                   class="{{ $field }} {{ $border('capacity') }}">
            <p id="capacity-hint" class="text-xs mt-2 text-gray-500">
                No puede ser mayor que la capacidad del aula.
            </p>
            @error('capacity')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="duration_hours" class="block text-sm font-semibold text-upp-primary mb-2">
                Horas acreditables <span class="text-red-600">*</span>
            </label>
            <input type="number" id="duration_hours" name="duration_hours" min="0.5" max="24" step="0.5" required
                   value="{{ old('duration_hours', $activity ? (float) $activity->duration_hours : null) }}"
                   placeholder="Ej. 2"
                   class="{{ $field }} {{ $border('duration_hours') }}">
            <p class="text-xs mt-2 text-gray-500">
                Se calculan con el horario; puedes ajustarlas. Se acreditarán al alumno cuando se registre su asistencia.
            </p>
            @error('duration_hours')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

    </div>

</section>

<!-- 03. Personal asignado -->
<section class="mb-8">

    <h2 class="font-heading text-lg font-bold text-upp-primary pb-3 mb-6 border-b border-upp-surface-high">
        03. Personal asignado
    </h2>

    <!-- Responsable -->
    <div class="mb-6">
        <label for="responsible_id" class="block text-sm font-semibold text-upp-primary mb-2">
            Responsable del pase de lista <span class="text-red-600">*</span>
        </label>

        @if ($responsibles->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
                Aún no hay usuarios con rol de responsable o docente.
                @if (Route::has('admin.responsibles.create'))
                    <a href="{{ route('admin.responsibles.create') }}" class="font-semibold underline">Registra un responsable</a>
                    para poder programar actividades.
                @else
                    Se registrarán en el módulo de Responsables.
                @endif
            </div>
        @else
            <select id="responsible_id" name="responsible_id" required class="{{ $field }} {{ $border('responsible_id') }}">
                <option value="">Seleccionar responsable</option>
                @foreach ($responsibleGroups as $group => $users)
                    @if ($users->isNotEmpty())
                        <optgroup label="{{ $group }}">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('responsible_id', $activity?->responsible_id) == $user->id)>
                                    {{ $user->name }} · {{ $user->email }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
            <p class="text-xs mt-2 text-gray-500">
                De preferencia, un responsable. Los docentes también pueden pasar lista.
            </p>
        @endif

        @error('responsible_id')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <!-- Ponentes -->
    <div>
        <p class="block text-sm font-semibold text-upp-primary mb-2">
            Ponentes <span class="text-red-600">*</span>
            <span class="font-normal text-gray-500">(puedes elegir varios)</span>
        </p>

        <div class="grid gap-2 sm:grid-cols-2 max-h-72 overflow-y-auto rounded-lg border p-3 {{ $border('speakers') }}">
            @forelse ($speakers as $speaker)
                <label class="flex items-center gap-3 rounded-lg px-3 py-2 cursor-pointer hover:bg-upp-surface-low has-[:checked]:bg-upp-surface-high">
                    <input type="checkbox" name="speakers[]" value="{{ $speaker->id }}"
                           @checked(in_array($speaker->id, $selectedSpeakers))
                           class="w-4 h-4 accent-upp-primary-container">
                    <span class="w-8 h-8 rounded-full bg-white ring-1 ring-upp-surface-high text-upp-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                        {{ $speaker->initials }}
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-upp-primary truncate">{{ $speaker->name }}</span>
                        @if ($speaker->matricula_id)
                            <span class="block text-xs text-gray-500">{{ $speaker->matricula_id }}</span>
                        @endif
                    </span>
                </label>
            @empty
                <p class="text-sm text-gray-500 px-1 py-2 sm:col-span-2">
                    No hay ponentes registrados.
                    <a href="{{ route('admin.speakers.create') }}" class="text-upp-primary-container font-medium hover:underline">Registra uno</a>
                    antes de programar la actividad.
                </p>
            @endforelse
        </div>

        @error('speakers')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
        @error('speakers.*')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

</section>

@push('scripts')
    <script>
        (() => {
            const room = document.getElementById('room_id');
            const capacity = document.getElementById('capacity');
            const capacityHint = document.getElementById('capacity-hint');
            const start = document.getElementById('start_time');
            const end = document.getElementById('end_time');
            const hours = document.getElementById('duration_hours');

            // Usa la capacidad del aula elegida como máximo del cupo
            const updateCapacity = () => {
                const max = room.selectedOptions[0]?.dataset.capacity;

                if (max) {
                    capacity.max = max;
                    capacityHint.textContent = `Máximo ${max} (capacidad del aula seleccionada).`;
                } else {
                    capacity.removeAttribute('max');
                    capacityHint.textContent = 'No puede ser mayor que la capacidad del aula.';
                }
            };

            // Sugiere las horas acreditables según la duración de la sesión,
            // mientras el usuario no las haya escrito a mano
            let autoHours = hours.value === '';
            hours.addEventListener('input', () => autoHours = false);

            const updateHours = () => {
                if (!autoHours || !start.value || !end.value) return;

                const [sh, sm] = start.value.split(':').map(Number);
                const [eh, em] = end.value.split(':').map(Number);
                const diff = ((eh * 60 + em) - (sh * 60 + sm)) / 60;

                if (diff > 0) hours.value = Math.floor(diff * 2) / 2;
            };

            room.addEventListener('change', updateCapacity);
            start.addEventListener('change', updateHours);
            end.addEventListener('change', updateHours);
            updateCapacity();
        })();
    </script>
@endpush
