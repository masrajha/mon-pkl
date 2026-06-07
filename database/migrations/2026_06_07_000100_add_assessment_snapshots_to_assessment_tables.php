<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('seminar_requests', 'assessment_rubric_snapshot')) {
                $table->json('assessment_rubric_snapshot')->nullable()->after('assessment_scores');
            }
        });

        Schema::table('field_supervisor_assessments', function (Blueprint $table): void {
            if (! Schema::hasColumn('field_supervisor_assessments', 'rubric_snapshot')) {
                $table->json('rubric_snapshot')->nullable()->after('scores');
            }

            if (! Schema::hasColumn('field_supervisor_assessments', 'survey_snapshot')) {
                $table->json('survey_snapshot')->nullable()->after('institution_feedback');
            }
        });
    }

    public function down(): void
    {
        Schema::table('field_supervisor_assessments', function (Blueprint $table): void {
            if (Schema::hasColumn('field_supervisor_assessments', 'survey_snapshot')) {
                $table->dropColumn('survey_snapshot');
            }

            if (Schema::hasColumn('field_supervisor_assessments', 'rubric_snapshot')) {
                $table->dropColumn('rubric_snapshot');
            }
        });

        Schema::table('seminar_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('seminar_requests', 'assessment_rubric_snapshot')) {
                $table->dropColumn('assessment_rubric_snapshot');
            }
        });
    }
};
