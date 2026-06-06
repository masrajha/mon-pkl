<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_assessments', function (Blueprint $table): void {
            $table->string('document_number')->nullable()->after('note');
            $table->string('document_city')->nullable()->after('document_number');
            $table->string('chair_name')->nullable()->after('document_city');
            $table->string('chair_identifier')->nullable()->after('chair_name');
            $table->string('coordinator_name')->nullable()->after('chair_identifier');
            $table->string('coordinator_identifier')->nullable()->after('coordinator_name');
            $table->json('document_header_snapshot')->nullable()->after('coordinator_identifier');
        });
    }

    public function down(): void
    {
        Schema::table('final_assessments', function (Blueprint $table): void {
            $table->dropColumn([
                'document_number',
                'document_city',
                'chair_name',
                'chair_identifier',
                'coordinator_name',
                'coordinator_identifier',
                'document_header_snapshot',
            ]);
        });
    }
};
