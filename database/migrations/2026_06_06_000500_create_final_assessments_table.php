<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('lecturer_score', 5, 2);
            $table->decimal('field_supervisor_score', 5, 2);
            $table->decimal('lecturer_weight', 5, 2)->default(50);
            $table->decimal('field_supervisor_weight', 5, 2)->default(50);
            $table->decimal('base_score', 5, 2);
            $table->decimal('suggested_deduction', 5, 2)->default(0);
            $table->decimal('final_deduction', 5, 2)->default(0);
            $table->decimal('final_score', 5, 2);
            $table->text('note')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_assessments');
    }
};
