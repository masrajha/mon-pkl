<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('daily_logs');
    }

    public function down(): void
    {
        // Catatan harian tidak memakai tabel terpisah; datanya berasal dari pasangan presensi.
    }
};
