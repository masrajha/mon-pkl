<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('proposed_date')->nullable();
            $table->time('proposed_time')->nullable();
            $table->string('mode', 20)->default('offline');
            $table->string('location')->nullable();
            $table->text('meeting_url')->nullable();
            $table->string('approval_method', 30)->default('system');
            $table->string('seminar_document_path')->nullable();
            $table->string('manual_acc_path')->nullable();
            $table->string('status', 40)->default('waiting_lecturer_approval')->index();
            $table->text('student_note')->nullable();
            $table->text('lecturer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('lecturer_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lecturer_approved_at')->nullable();
            $table->foreignId('manual_acc_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manual_acc_validated_at')->nullable();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('seminar_score', 5, 2)->nullable();
            $table->text('seminar_score_note')->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'status']);
            $table->index(['approval_method', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_requests');
    }
};
