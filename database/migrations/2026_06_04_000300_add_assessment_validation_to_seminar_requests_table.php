<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_requests', function (Blueprint $table): void {
            $table->foreignId('assessment_validated_by')->nullable()->after('assessment_file_path')->constrained('users')->nullOnDelete();
            $table->timestamp('assessment_validated_at')->nullable()->after('assessment_validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('seminar_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assessment_validated_by');
            $table->dropColumn('assessment_validated_at');
        });
    }
};
