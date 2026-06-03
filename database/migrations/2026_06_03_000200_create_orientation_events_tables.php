<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orientation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('location_name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('max_distance_meters')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['internship_period_id', 'study_program_id', 'is_active'], 'orientation_events_scope_idx');
        });

        Schema::create('orientation_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('orientation_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->decimal('student_latitude', 10, 7);
            $table->decimal('student_longitude', 10, 7);
            $table->decimal('event_latitude', 10, 7);
            $table->decimal('event_longitude', 10, 7);
            $table->unsignedInteger('distance_meters')->nullable();
            $table->json('device_info')->nullable();
            $table->string('source_url')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->unique(['orientation_event_id', 'student_id'], 'orientation_attendance_once_unique');
            $table->index(['orientation_event_id', 'checked_at'], 'orientation_attendance_event_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orientation_attendances');
        Schema::dropIfExists('orientation_events');
    }
};
