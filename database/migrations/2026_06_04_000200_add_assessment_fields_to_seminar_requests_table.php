<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_requests', function (Blueprint $table): void {
            $table->string('assessment_method', 20)->nullable()->after('seminar_score_note');
            $table->json('assessment_scores')->nullable()->after('assessment_method');
            $table->string('assessment_file_path')->nullable()->after('assessment_scores');
        });
    }

    public function down(): void
    {
        Schema::table('seminar_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'assessment_method',
                'assessment_scores',
                'assessment_file_path',
            ]);
        });
    }
};
