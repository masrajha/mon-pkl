<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->text('avatar_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::statement('UPDATE users SET avatar_url = LEFT(avatar_url, 255) WHERE CHAR_LENGTH(avatar_url) > 255');

        Schema::table('users', function ($table) {
            $table->string('avatar_url')->nullable()->change();
        });
    }
};
