<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'internship_periods_name_academic_year_semester_batch_unique';
    private const NEW_INDEX = 'periods_program_name_year_semester_batch_unique';

    public function up(): void
    {
        $this->withoutForeignKeyChecks(function (): void {
            if ($this->indexExists('internship_periods', self::OLD_INDEX)) {
                Schema::table('internship_periods', function (Blueprint $table): void {
                    $table->dropUnique(self::OLD_INDEX);
                });
            }

            if (! $this->indexExists('internship_periods', self::NEW_INDEX)) {
                Schema::table('internship_periods', function (Blueprint $table): void {
                    $table->unique(['program_id', 'name', 'academic_year', 'semester', 'batch'], self::NEW_INDEX);
                });
            }
        });
    }

    public function down(): void
    {
        $this->withoutForeignKeyChecks(function (): void {
            if ($this->indexExists('internship_periods', self::NEW_INDEX)) {
                Schema::table('internship_periods', function (Blueprint $table): void {
                    $table->dropUnique(self::NEW_INDEX);
                });
            }

            if (! $this->indexExists('internship_periods', self::OLD_INDEX)) {
                Schema::table('internship_periods', function (Blueprint $table): void {
                    $table->unique(['name', 'academic_year', 'semester', 'batch'], self::OLD_INDEX);
                });
            }
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

    private function withoutForeignKeyChecks(callable $callback): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $callback();

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $callback();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
