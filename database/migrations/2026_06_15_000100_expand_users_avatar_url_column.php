<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY avatar_url TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE users SET avatar_url = LEFT(avatar_url, 255) WHERE CHAR_LENGTH(avatar_url) > 255');
        DB::statement('ALTER TABLE users MODIFY avatar_url VARCHAR(255) NULL');
    }
};
