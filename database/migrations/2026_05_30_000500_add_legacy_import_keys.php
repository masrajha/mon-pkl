<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->unique()->after('google_id');
        });

        Schema::table('internship_places', function (Blueprint $table) {
            $table->string('legacy_firebase_key')->nullable()->unique()->after('id');
            $table->string('legacy_source_file')->nullable()->after('legacy_firebase_key');
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->string('legacy_firebase_key')->nullable()->unique()->after('id');
            $table->string('legacy_source_file')->nullable()->after('legacy_firebase_key');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropColumn(['legacy_firebase_key', 'legacy_source_file']);
        });

        Schema::table('internship_places', function (Blueprint $table) {
            $table->dropColumn(['legacy_firebase_key', 'legacy_source_file']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('firebase_uid');
        });
    }
};
