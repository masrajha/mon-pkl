<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('field_supervisor_access_tokens')) {
            return;
        }

        Schema::create('field_supervisor_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('internship_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['internship_enrollment_id', 'revoked_at']);
            $table->index(['email', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_supervisor_access_tokens');
    }
};
