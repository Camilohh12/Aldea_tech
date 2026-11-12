<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Grupos que atiende cada tutor, con la nomenclatura de la coordinación
// (p. ej. «TIID_01_02, TIID_04_01»). Solo agrega una columna.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('grupos_tutoria', 160)->nullable()->after('grupo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('grupos_tutoria');
        });
    }
};
