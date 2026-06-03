<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->string('field_supervisor_email')->nullable()->after('field_supervisor_phone');
        });

        Schema::table('supervisor_change_requests', function (Blueprint $table): void {
            $table->string('current_field_supervisor_email')->nullable()->after('current_field_supervisor_phone');
            $table->string('requested_field_supervisor_email')->nullable()->after('requested_field_supervisor_phone');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_change_requests', function (Blueprint $table): void {
            $table->dropColumn(['current_field_supervisor_email', 'requested_field_supervisor_email']);
        });

        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->dropColumn('field_supervisor_email');
        });
    }
};
