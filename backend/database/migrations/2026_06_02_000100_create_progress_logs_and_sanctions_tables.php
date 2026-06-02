<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('deadline_type', 50);
            $table->string('file_path');
            $table->timestamp('uploaded_at');
            $table->string('status', 20)->default('pending');
            $table->text('lecturer_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('sanction_points')->default(0);
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'deadline_type']);
            $table->index(['status', 'uploaded_at']);
        });

        Schema::create('sanctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_progress_id')->nullable()->constrained('submission_progress')->nullOnDelete();
            $table->string('sanction_type', 50);
            $table->unsignedInteger('points_deducted');
            $table->text('reason')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'sanction_type', 'date']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions');
        Schema::dropIfExists('submission_progress');
    }
};
