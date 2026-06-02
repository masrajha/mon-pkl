<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('internship_places') || Schema::hasColumn('internship_places', 'location')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => $this->addPostgisLocation(),
            'mysql' => $this->addMysqlLocation(),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('internship_places') || ! Schema::hasColumn('internship_places', 'location')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE internship_places DROP COLUMN IF EXISTS location'),
            'mysql' => DB::statement('ALTER TABLE internship_places DROP COLUMN location'),
            default => null,
        };
    }

    private function addPostgisLocation(): void
    {
        if (! $this->postgisInstalled()) {
            throw new RuntimeException(
                'PostGIS extension is available on the server but is not enabled for database "monpkl". '.
                'Run "CREATE EXTENSION IF NOT EXISTS postgis;" once as a PostgreSQL superuser, then run migrations again.'
            );
        }

        DB::statement('ALTER TABLE internship_places ADD COLUMN location geography(Point, 4326) NULL');
        DB::statement('CREATE INDEX internship_places_location_gist ON internship_places USING GIST (location)');
        DB::statement(<<<'SQL'
            UPDATE internship_places
            SET location = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography
            WHERE latitude IS NOT NULL
              AND longitude IS NOT NULL
        SQL);
    }

    private function addMysqlLocation(): void
    {
        DB::statement('ALTER TABLE internship_places ADD COLUMN location POINT NULL');
    }

    private function postgisInstalled(): bool
    {
        $result = DB::selectOne("select exists (select 1 from pg_extension where extname = 'postgis') as installed");

        return (bool) ($result?->installed ?? false);
    }
};
