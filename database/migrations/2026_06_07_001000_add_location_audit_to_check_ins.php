<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->unsignedInteger('student_location_accuracy_meters')->nullable()->after('distance_meters');
            $table->string('location_status')->default('valid')->after('student_location_accuracy_meters')->index();
            $table->json('location_flags')->nullable()->after('location_status');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropColumn([
                'student_location_accuracy_meters',
                'location_status',
                'location_flags',
            ]);
        });
    }
};
