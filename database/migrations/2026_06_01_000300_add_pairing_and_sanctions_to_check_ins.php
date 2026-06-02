<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->string('action', 20)->nullable()->after('type')->index();
            $table->unsignedBigInteger('pair_id')->nullable()->after('checked_at')->index();
            $table->unsignedInteger('duration_minutes')->nullable()->after('distance_meters');
            $table->unsignedInteger('sanction_points')->default(0)->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropColumn([
                'action',
                'pair_id',
                'duration_minutes',
                'sanction_points',
            ]);
        });
    }
};
