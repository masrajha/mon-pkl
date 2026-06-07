<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_in_location_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->decimal('gps_latitude', 10, 7);
            $table->decimal('gps_longitude', 10, 7);
            $table->unsignedInteger('gps_accuracy_meters')->nullable();
            $table->string('source')->default('browser_geolocation');
            $table->timestamp('captured_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'internship_enrollment_id', 'captured_at'], 'checkin_loc_samples_user_enrollment_time_idx');
        });

        Schema::table('check_ins', function (Blueprint $table): void {
            $table->foreignId('check_in_location_sample_id')
                ->nullable()
                ->after('forgotten_attendance_request_id')
                ->constrained('check_in_location_samples')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('check_in_location_sample_id');
        });

        Schema::dropIfExists('check_in_location_samples');
    }
};
