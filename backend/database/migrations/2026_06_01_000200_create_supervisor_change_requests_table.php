<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_lecturer_supervisor_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->foreignId('requested_lecturer_supervisor_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->string('current_field_supervisor')->nullable();
            $table->string('requested_field_supervisor')->nullable();
            $table->string('current_field_supervisor_phone')->nullable();
            $table->string('requested_field_supervisor_phone')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'status'], 'supervisor_change_enrollment_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_change_requests');
    }
};
