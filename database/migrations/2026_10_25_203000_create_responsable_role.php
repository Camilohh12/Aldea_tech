<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    // Rol para los encargados de supervisar una actividad y pasar lista.
    // findOrCreate no duplica el rol si ya existe.
    public function up(): void
    {
        Role::findOrCreate('responsable', 'web');
    }

    public function down(): void
    {
        Role::where('name', 'responsable')
            ->where('guard_name', 'web')
            ->delete();
    }
};
