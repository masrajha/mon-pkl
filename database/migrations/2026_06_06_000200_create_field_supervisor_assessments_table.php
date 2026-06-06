<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_supervisor_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('scores');
            $table->decimal('discipline_score', 5, 2);
            $table->decimal('teamwork_score', 5, 2);
            $table->decimal('performance_score', 5, 2);
            $table->decimal('final_score', 5, 2);
            $table->text('note')->nullable();
            $table->text('student_general_note')->nullable();
            $table->text('student_recommendation')->nullable();
            $table->json('institution_feedback')->nullable();
            $table->text('institution_note')->nullable();
            $table->string('assessed_by_name')->nullable();
            $table->string('assessed_by_email')->index();
            $table->string('assessment_mode', 20);
            $table->timestamp('assessed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_supervisor_assessments');
    }
};
