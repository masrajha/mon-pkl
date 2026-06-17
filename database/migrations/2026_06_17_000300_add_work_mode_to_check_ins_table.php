<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->string('work_mode', 20)->default('onsite')->after('action')->index();
            $table->foreignId('wfa_request_id')->nullable()->after('work_mode')->constrained('wfa_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('wfa_request_id');
            $table->dropColumn('work_mode');
        });
    }
};
