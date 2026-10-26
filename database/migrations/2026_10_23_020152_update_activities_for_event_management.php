
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('duration_hours', 5, 2)->nullable();

            $table->foreignId('responsible_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('activity_speaker', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->foreignId('speaker_id')
                ->constrained('speakers')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['activity_id', 'speaker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_speaker');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);

            $table->dropColumn([
                'capacity',
                'duration_hours',
                'responsible_id'
            ]);
        });
    }
};
