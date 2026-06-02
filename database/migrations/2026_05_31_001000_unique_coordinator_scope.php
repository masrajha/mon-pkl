<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('internship_coordinators', 'internship_coordinators_unique_scope')) {
            return;
        }

        Schema::table('internship_coordinators', function (Blueprint $table): void {
            $table->unique(['internship_period_id', 'study_program_id'], 'internship_coordinators_unique_scope');
        });
    }

    public function down(): void
    {
        if (! $this->indexExists('internship_coordinators', 'internship_coordinators_unique_scope')) {
            return;
        }

        Schema::table('internship_coordinators', function (Blueprint $table): void {
            $table->dropUnique('internship_coordinators_unique_scope');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            return DB::table('information_schema.statistics')
                ->whereRaw('table_schema = database()')
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        return false;
    }
};
