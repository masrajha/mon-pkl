<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_supervisor_assessments', function (Blueprint $table): void {
            if (! Schema::hasColumn('field_supervisor_assessments', 'student_general_note')) {
                $table->text('student_general_note')->nullable()->after('note');
            }

            if (! Schema::hasColumn('field_supervisor_assessments', 'student_recommendation')) {
                $table->text('student_recommendation')->nullable()->after('student_general_note');
            }

            if (! Schema::hasColumn('field_supervisor_assessments', 'institution_feedback')) {
                $table->json('institution_feedback')->nullable()->after('student_recommendation');
            }

            if (! Schema::hasColumn('field_supervisor_assessments', 'institution_note')) {
                $table->text('institution_note')->nullable()->after('institution_feedback');
            }
        });
    }

    public function down(): void
    {
        Schema::table('field_supervisor_assessments', function (Blueprint $table): void {
            foreach ([
                'institution_note',
                'institution_feedback',
                'student_recommendation',
                'student_general_note',
            ] as $column) {
                if (Schema::hasColumn('field_supervisor_assessments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
