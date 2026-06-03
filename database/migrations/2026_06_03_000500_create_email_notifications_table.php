<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 191)->unique();
            $table->string('type', 120)->index();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->json('body_lines');
            $table->string('action_text')->nullable();
            $table->text('action_url')->nullable();
            $table->nullableMorphs('notifiable');
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['recipient_email', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notifications');
    }
};
