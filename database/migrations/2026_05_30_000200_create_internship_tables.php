<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('field_supervisor_name')->nullable();
            $table->string('field_supervisor_phone')->nullable();
            $table->string('contact_student_phone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('visited')->default(false);
            $table->timestamp('legacy_created_at')->nullable();
            $table->timestamps();

            $table->index(['city_id', 'name']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('npm')->unique();
            $table->string('full_name');
            $table->timestamps();
        });

        Schema::create('internship_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('internship_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('internship_place_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lecturer_supervisor')->nullable();
            $table->string('field_supervisor')->nullable();
            $table->string('contact_student_phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('legacy_source_file')->nullable();
            $table->string('legacy_period_label')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'study_program_id', 'internship_period_id'], 'enrollments_student_program_period_unique');
            $table->index(['internship_period_id', 'study_program_id', 'status'], 'enrollments_period_program_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_enrollments');
        Schema::dropIfExists('students');

        Schema::dropIfExists('internship_places');
    }
};
