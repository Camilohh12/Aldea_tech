<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    // 1. Tipos de actividades (Conferencia, Taller, Curso, Foro)
    Schema::create('activity_types', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->timestamps();
    });

    // 2. Aulas y Espacios
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('capacity')->default(0);
        $table->timestamps();
    });

    // 3. Ponentes
    Schema::create('speakers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('matricula_id')->nullable();
        $table->text('bio')->nullable();
        $table->timestamps();
    });

    // 4. Actividades del evento
    Schema::create('activities', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();
        $table->foreignId('activity_type_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('room_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('speaker_id')->nullable()->constrained()->onDelete('cascade');
        $table->date('date')->nullable();
        $table->time('start_time')->nullable();
        $table->time('end_time')->nullable();
        $table->boolean('checkin_enabled')->default(false); // Para el switch del Admin
        $table->timestamps();
    });

    // 5. Inscripciones y Asistencia de Alumnos
    Schema::create('enrollments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('activity_id')->constrained()->onDelete('cascade');
        $table->enum('status', ['registered', 'cancelled'])->default('registered');
        $table->boolean('attended')->default(false);
        $table->timestamp('checkin_time')->nullable();
        $table->boolean('synced_offline')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aldea_tables');
    }
};
