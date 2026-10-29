<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plantilla general de los certificados de ponentes (una sola fila).
        // Si no existe, el modelo la crea con los textos originales.
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('institution');
            $table->string('event_name');
            $table->string('heading');
            $table->string('title');
            $table->text('body');
            $table->string('signer1_name');
            $table->string('signer1_title');
            $table->string('signer2_name')->nullable();
            $table->string('signer2_title')->nullable();
            $table->string('footer')->nullable();
            $table->timestamps();
        });

        // Certificados emitidos: uno por ponente y actividad
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->string('folio')->unique();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['activity_id', 'speaker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
