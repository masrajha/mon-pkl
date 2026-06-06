<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->timestamp('daily_log_validated_at')->nullable()->after('sanction_points');
            $table->string('daily_log_validated_by_name')->nullable()->after('daily_log_validated_at');
            $table->string('daily_log_validated_by_email')->nullable()->after('daily_log_validated_by_name')->index();
            $table->string('daily_log_validation_mode', 20)->nullable()->after('daily_log_validated_by_email');
            $table->text('daily_log_validation_note')->nullable()->after('daily_log_validation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropColumn([
                'daily_log_validated_at',
                'daily_log_validated_by_name',
                'daily_log_validated_by_email',
                'daily_log_validation_mode',
                'daily_log_validation_note',
            ]);
        });
    }
};
