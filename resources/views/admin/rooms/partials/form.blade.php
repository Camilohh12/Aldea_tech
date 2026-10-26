{{-- Campos del aula, compartidos por create y edit.
     Recibe $room (null al crear) y, opcionalmente, $minCapacity. --}}
@php
    $minCapacity = $minCapacity ?? 0;
@endphp

<!-- Nombre del aula -->
<div class="mb-6">

    <label for="name" class="block text-sm font-semibold text-upp-primary mb-2">
        Nombre del aula <span class="text-red-600">*</span>
    </label>

    <input
        type="text"
        id="name"
        name="name"
        value="{{ old('name', $room?->name) }}"
        placeholder="Ej. Laboratorio de Redes"
        maxlength="255"
        required
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}"
    >

    @error('name')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>

<!-- Capacidad -->
<div class="mb-8">

    <label for="capacity" class="block text-sm font-semibold text-upp-primary mb-2">
        Capacidad máxima <span class="text-red-600">*</span>
    </label>

    <input
        type="number"
        id="capacity"
        name="capacity"
        value="{{ old('capacity', $room?->capacity) }}"
        placeholder="Ej. 30"
        min="{{ max(1, $minCapacity) }}"
        max="10000"
        required
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('capacity') ? 'border-red-400' : 'border-gray-300' }}"
    >

    @if ($minCapacity > 0)
        <p class="text-xs mt-2 text-upp-secondary">
            Esta aula tiene actividades con cupo de hasta
            <strong>{{ $minCapacity }}</strong> personas; la capacidad no puede ser menor.
        </p>
    @else
        <p class="text-xs mt-2 text-gray-500">
            Número máximo de participantes que puede recibir el aula.
        </p>
    @endif

    @error('capacity')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>
