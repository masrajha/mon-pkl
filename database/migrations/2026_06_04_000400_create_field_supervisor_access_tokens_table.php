<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('field_supervisor_access_tokens')) {
            Schema::table('field_supervisor_access_tokens', function (Blueprint $table): void {
                if (! Schema::hasIndex('field_supervisor_access_tokens', 'fsat_enrollment_revoked_idx')) {
                    $table->index(['internship_enrollment_id', 'revoked_at'], 'fsat_enrollment_revoked_idx');
                }

                if (! Schema::hasIndex('field_supervisor_access_tokens', 'fsat_email_expires_idx')) {
                    $table->index(['email', 'expires_at'], 'fsat_email_expires_idx');
                }
            });

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

            $table->index(['internship_enrollment_id', 'revoked_at'], 'fsat_enrollment_revoked_idx');
            $table->index(['email', 'expires_at'], 'fsat_email_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_supervisor_access_tokens');
    }
};
