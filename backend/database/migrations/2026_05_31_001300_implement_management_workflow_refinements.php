<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_places', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('visited')->index();
        });

        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->boolean('has_krs_pkl')->default(false)->after('contact_student_phone');
            $table->unsignedSmallInteger('total_sks')->nullable()->after('has_krs_pkl');
            $table->unsignedTinyInteger('current_semester')->nullable()->after('total_sks');
            $table->decimal('gpa', 3, 2)->nullable()->after('current_semester');
            $table->text('admin_note')->nullable()->after('status');
            $table->text('final_report_path')->nullable()->after('admin_note');
            $table->unsignedInteger('total_sanctions_points')->default(0)->after('final_report_path');
        });

        Schema::create('period_deadlines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_period_id')->constrained()->cascadeOnDelete();
            $table->string('deadline_type', 50);
            $table->date('deadline_date');
            $table->unsignedInteger('penalty_points')->default(5);
            $table->boolean('is_fixed_penalty')->default(false);
            $table->timestamps();

            $table->unique(['internship_period_id', 'deadline_type']);
            $table->index(['deadline_type', 'deadline_date']);
        });

        Schema::create('relocation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_internship_place_id')->nullable()->constrained('internship_places')->nullOnDelete();
            $table->foreignId('new_internship_place_id')->constrained('internship_places')->restrictOnDelete();
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relocation_requests');
        Schema::dropIfExists('period_deadlines');

        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->dropColumn([
                'has_krs_pkl',
                'total_sks',
                'current_semester',
                'gpa',
                'admin_note',
                'final_report_path',
                'total_sanctions_points',
            ]);
        });

        Schema::table('internship_places', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
