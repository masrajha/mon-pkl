<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_period_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_period_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('settings');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_period_settings');
    }
};
