<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            $table->string('degree_level', 10)->default('S1')->after('name')->index();
        });

        DB::table('study_programs')
            ->where(function ($query): void {
                $query->where('code', 'like', 'D3%')
                    ->orWhere('name', 'like', 'D3%')
                    ->orWhere('name', 'like', '%Diploma%');
            })
            ->update(['degree_level' => 'D3']);
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            $table->dropIndex(['degree_level']);
            $table->dropColumn('degree_level');
        });
    }
};
