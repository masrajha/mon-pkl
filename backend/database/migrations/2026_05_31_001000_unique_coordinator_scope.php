<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_coordinators', function (Blueprint $table) {
            $table->dropUnique('internship_coordinators_unique_assignment');
            $table->unique(['internship_period_id', 'study_program_id'], 'internship_coordinators_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::table('internship_coordinators', function (Blueprint $table) {
            $table->dropUnique('internship_coordinators_unique_scope');
            $table->unique(['lecturer_id', 'internship_period_id', 'study_program_id'], 'internship_coordinators_unique_assignment');
        });
    }
};
