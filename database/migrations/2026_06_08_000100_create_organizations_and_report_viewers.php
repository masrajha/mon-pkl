<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('study_programs', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('degree_level')->constrained('organizations')->nullOnDelete();
        });

        Schema::create('report_viewer_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 40);
            $table->string('status', 20)->default('active')->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['lecturer_id', 'organization_id', 'study_program_id', 'level'], 'report_viewer_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_viewer_assignments');

        Schema::table('study_programs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};
