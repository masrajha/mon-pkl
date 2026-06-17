<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wfa_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('planned_location');
            $table->decimal('planned_latitude', 10, 7)->nullable();
            $table->decimal('planned_longitude', 10, 7)->nullable();
            $table->text('planned_activity');
            $table->text('reason');
            $table->string('evidence_path');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'starts_at', 'ends_at'], 'wfa_requests_enrollment_dates_index');
            $table->index(['internship_enrollment_id', 'status'], 'wfa_requests_enrollment_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wfa_requests');
    }
};
