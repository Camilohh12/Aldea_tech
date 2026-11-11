<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Solo agrega una columna (no borra ni cambia datos): los alumnos ya
// registrados la tienen vacía hasta que la capturen en «Mi perfil».
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Código del grupo del alumno: prefijo de carrera, grupo y cuatrimestre (sftw_01_09)
            $table->string('grupo', 20)->nullable()->after('cuatrimestre')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['grupo']);
            $table->dropColumn('grupo');
        });
    }
};
