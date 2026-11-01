<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Alumno:
 * - capacity: cupo propio de la actividad (si es null se usa el cupo del aula).
 * - checkin_token: código que va dentro del QR que se muestra en el aula;
 *   el alumno lo escanea para registrar su asistencia.
 * - Índice único en enrollments para que un alumno no se inscriba dos veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // capacity también la agrega la migración del panel admin (2026_10_23);
            // en bases donde esa corrió primero la columna ya existe
            if (! Schema::hasColumn('activities', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('speaker_id');
            }
            $table->string('checkin_token', 64)->nullable()->unique()->after('checkin_enabled');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['user_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'activity_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique(['checkin_token']);
            $table->dropColumn(['capacity', 'checkin_token']);
        });
    }
};
