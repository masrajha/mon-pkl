<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->string('daily_log_status', 20)->default('pending')->index()->after('daily_log_validation_note');
            $table->timestamp('daily_log_flagged_at')->nullable()->after('daily_log_status');
            $table->string('daily_log_flagged_by_name')->nullable()->after('daily_log_flagged_at');
            $table->string('daily_log_flagged_by_email')->nullable()->index()->after('daily_log_flagged_by_name');
            $table->string('daily_log_flag_mode', 20)->nullable()->after('daily_log_flagged_by_email');
            $table->text('daily_log_flag_reason')->nullable()->after('daily_log_flag_mode');
            $table->text('daily_log_student_clarification')->nullable()->after('daily_log_flag_reason');
            $table->timestamp('daily_log_clarified_at')->nullable()->after('daily_log_student_clarification');
        });

        DB::table('check_ins')
            ->whereNotNull('daily_log_validated_at')
            ->update(['daily_log_status' => 'validated']);
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropColumn([
                'daily_log_status',
                'daily_log_flagged_at',
                'daily_log_flagged_by_name',
                'daily_log_flagged_by_email',
                'daily_log_flag_mode',
                'daily_log_flag_reason',
                'daily_log_student_clarification',
                'daily_log_clarified_at',
            ]);
        });
    }
};
