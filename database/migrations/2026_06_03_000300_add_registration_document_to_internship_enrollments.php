<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->string('registration_document_path')->nullable()->after('admin_note');
        });
    }

    public function down(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table): void {
            $table->dropColumn('registration_document_path');
        });
    }
};
