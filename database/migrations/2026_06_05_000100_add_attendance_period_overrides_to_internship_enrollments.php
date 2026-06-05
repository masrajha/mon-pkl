<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->date('attendance_starts_at')->nullable()->after('internship_place_id');
            $table->date('attendance_ends_at')->nullable()->after('attendance_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->dropColumn(['attendance_starts_at', 'attendance_ends_at']);
        });
    }
};
