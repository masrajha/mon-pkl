<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_assessments', function (Blueprint $table): void {
            $table->string('verification_token', 64)->nullable()->unique()->after('document_header_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('final_assessments', function (Blueprint $table): void {
            $table->dropUnique(['verification_token']);
            $table->dropColumn('verification_token');
        });
    }
};
