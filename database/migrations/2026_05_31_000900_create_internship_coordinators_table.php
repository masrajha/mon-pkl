<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_coordinators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['lecturer_id', 'internship_period_id', 'study_program_id'], 'internship_coordinators_assignment_index');
            $table->unique(['internship_period_id', 'study_program_id'], 'internship_coordinators_unique_scope');
            $table->index(['internship_period_id', 'study_program_id', 'status'], 'internship_coordinators_scope_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_coordinators');
    }
};
