{{-- Campos del ponente, compartidos por create y edit.
     Recibe $speaker (null al registrar). --}}

<!-- Nombre -->
<div class="mb-6">

    <label for="name" class="block text-sm font-semibold text-upp-primary mb-2">
        Nombre completo <span class="text-red-600">*</span>
    </label>

    <input
        type="text"
        id="name"
        name="name"
        value="{{ old('name', $speaker?->name) }}"
        placeholder="Ej. Dra. Mónica P. Soto"
        maxlength="255"
        required
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}"
    >

    <p class="text-xs mt-2 text-gray-500">
        Incluye el grado si quieres que aparezca (Dr., Mtra., Ing.).
    </p>

    @error('name')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>

<!-- Matrícula -->
<div class="mb-6">

    <label for="matricula_id" class="block text-sm font-semibold text-upp-primary mb-2">
        Matrícula o número de empleado
        <span class="font-normal text-gray-500">(opcional)</span>
    </label>

    <input
        type="text"
        id="matricula_id"
        name="matricula_id"
        value="{{ old('matricula_id', $speaker?->matricula_id) }}"
        placeholder="Ej. UPP-2026-014"
        maxlength="50"
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('matricula_id') ? 'border-red-400' : 'border-gray-300' }}"
    >

    <p class="text-xs mt-2 text-gray-500">
        Déjalo vacío si el ponente es externo a la universidad.
    </p>

    @error('matricula_id')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>

<!-- Especialidad / semblanza -->
<div class="mb-8">

    <label for="bio" class="block text-sm font-semibold text-upp-primary mb-2">
        Especialidad / semblanza
        <span class="font-normal text-gray-500">(opcional)</span>
    </label>

    <textarea
        id="bio"
        name="bio"
        rows="4"
        maxlength="2000"
        placeholder="Ej. Investigadora en bioprocesos. Imparte el taller de Modelado Cinético."
        class="w-full rounded-lg border px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-upp-primary-container
               {{ $errors->has('bio') ? 'border-red-400' : 'border-gray-300' }}"
    >{{ old('bio', $speaker?->bio) }}</textarea>

    <p class="text-xs mt-2 text-gray-500">
        El inicio de este texto aparece debajo del nombre en la lista de ponentes.
    </p>

    @error('bio')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror

</div>
