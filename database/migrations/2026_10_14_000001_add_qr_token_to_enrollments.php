<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Cambio de flujo del pase de lista:
 * - Cada inscripción tiene su propio código (qr_token). El alumno lo muestra
 *   como QR y el tutor lo escanea.
 * - checked_in_by guarda quién registró la asistencia.
 * - Se elimina activities.checkin_token (el QR por aula ya no se usa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('status');
            $table->foreignId('checked_in_by')->nullable()->after('checkin_time')
                ->constrained('users')->nullOnDelete();
        });

        // Inscripciones que ya existían reciben su código.
        DB::table('enrollments')->whereNull('qr_token')->orderBy('id')->each(function ($row) {
            DB::table('enrollments')->where('id', $row->id)->update(['qr_token' => Str::random(40)]);
        });

        if (Schema::hasColumn('activities', 'checkin_token')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropUnique(['checkin_token']);
                $table->dropColumn('checkin_token');
            });
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('checkin_token', 64)->nullable()->unique()->after('checkin_enabled');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
