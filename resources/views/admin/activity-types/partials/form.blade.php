{{-- Campos del tipo de actividad, compartidos por create y edit.
     Recibe $activityType (null al crear) y, opcionalmente, $activitiesCount. --}}
@php
    $activitiesCount = $activitiesCount ?? 0;
@endphp

<!-- Nombre del tipo -->
<div class="mb-6">

    <label for="name" class="block text-sm font-semibold text-upp-primary mb-2">
        Nombre del tipo <span class="text-red-600">*</span>
    </label>

    <input
        type="text"
        id="name"
        name="name"
        value="{{ old('name', $activityType?->name) }}"
        placeholder="Ej. Hackatón"
        maxlength="60"
        required
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}"
    >

    @if ($activitiesCount > 0)
        <p class="text-xs mt-2 text-upp-secondary">
            Este tipo se usa en <strong>{{ $activitiesCount }}</strong>
            {{ $activitiesCount === 1 ? 'actividad' : 'actividades' }}; si cambias el nombre, se verá actualizado en todas.
        </p>
    @else
        <p class="text-xs mt-2 text-gray-500">
            Aparecerá en la lista «Tipo» al crear una actividad y en los filtros del catálogo.
        </p>
    @endif

    @error('name')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>

<!-- Descripción -->
<div class="mb-8">

    <label for="description" class="block text-sm font-semibold text-upp-primary mb-2">
        Descripción
    </label>

    <textarea
        id="description"
        name="description"
        rows="3"
        maxlength="500"
        placeholder="Ej. Competencia de desarrollo por equipos"
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('description') ? 'border-red-400' : 'border-gray-300' }}"
    >{{ old('description', $activityType?->description) }}</textarea>

    @error('description')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>
