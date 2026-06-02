<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('note')->nullable();
            $table->timestamp('checked_at')->index();
            $table->decimal('student_latitude', 10, 7)->nullable();
            $table->decimal('student_longitude', 10, 7)->nullable();
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->json('device_info')->nullable();
            $table->string('source_url')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('source_photo_url')->nullable();
            $table->string('legacy_geojson_type')->nullable();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'checked_at']);
            $table->index(['type', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
