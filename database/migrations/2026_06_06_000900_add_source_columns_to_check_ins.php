<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->string('source_type')->default('realtime')->after('action');
            $table->foreignId('forgotten_attendance_request_id')->nullable()->after('source_type')->constrained('forgotten_attendance_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('forgotten_attendance_request_id');
            $table->dropColumn('source_type');
        });
    }
};
