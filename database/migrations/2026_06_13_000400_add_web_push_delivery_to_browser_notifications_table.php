<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('browser_notifications', function (Blueprint $table): void {
            $table->timestamp('pushed_at')->nullable()->after('shown_at')->index();
            $table->unsignedSmallInteger('push_attempts')->default(0)->after('pushed_at');
            $table->text('push_last_error')->nullable()->after('push_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('browser_notifications', function (Blueprint $table): void {
            $table->dropColumn(['pushed_at', 'push_attempts', 'push_last_error']);
        });
    }
};
