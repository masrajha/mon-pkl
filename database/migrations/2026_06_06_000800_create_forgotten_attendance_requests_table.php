<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forgotten_attendance_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20);
            $table->date('requested_date');
            $table->time('requested_time');
            $table->timestamp('requested_checked_at');
            $table->text('note');
            $table->text('reason');
            $table->decimal('student_latitude', 10, 7)->nullable();
            $table->decimal('student_longitude', 10, 7)->nullable();
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->string('photo_path')->nullable();
            $table->json('device_info')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewed_by_name')->nullable();
            $table->string('reviewed_by_email')->nullable();
            $table->string('reviewed_by_role')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('created_check_in_id')->nullable()->constrained('check_ins')->nullOnDelete();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'requested_date', 'action'], 'forgotten_attendance_request_day_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forgotten_attendance_requests');
    }
};
