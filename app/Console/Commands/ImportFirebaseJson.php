<?php

namespace App\Console\Commands;

use App\Models\CheckIn;
use App\Models\City;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlace;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportFirebaseJson extends Command
{
    protected $signature = 'import:firebase-json
        {source=../ilkomunila-export (20220619-Periode Jan 2022).json : Firebase database export JSON}
        {--master=../ilkomunila-master-export.json : Firebase master/kota export JSON}
        {--study-program-code=ILKOM}
        {--study-program-name=Ilmu Komputer}
        {--academic-year=2021/2022}
        {--semester=Genap}
        {--batch=Jan 2022}
        {--period-name=Periode Jan 2022}
        {--legacy-period-label=Periode Jan 2022}
        {--dry-run : Parse and validate without writing to the database}';

    protected $description = 'Import legacy Firebase JSON export into the normalized Mon PKL database.';

    private array $report = [
        'source_file' => null,
        'master_file' => null,
        'dry_run' => false,
        'counts' => [
            'cities' => 0,
            'internship_places' => 0,
            'users' => 0,
            'students' => 0,
            'enrollments' => 0,
            'check_ins' => 0,
            'mon_user_check_ins' => 0,
        ],
        'warnings' => [],
        'failed' => [],
        'invalid_coordinates' => [],
        'mon_user_validation' => [],
    ];

    public function handle(): int
    {
        $sourcePath = $this->resolvePath($this->argument('source'));
        $masterPath = $this->resolvePath($this->option('master'));

        if (! is_file($sourcePath)) {
            $this->error("Source file not found: {$sourcePath}");

            return self::FAILURE;
        }

        $this->report['source_file'] = $sourcePath;
        $this->report['master_file'] = is_file($masterPath) ? $masterPath : null;
        $this->report['dry_run'] = (bool) $this->option('dry-run');

        $source = $this->readJson($sourcePath);
        $master = is_file($masterPath) ? $this->readJson($masterPath) : [];
        $sourceName = basename($sourcePath);

        $callback = function () use ($source, $master, $sourceName): void {
            $studyProgram = $this->importStudyProgram();
            $period = $this->importPeriod();

            $this->importCities($master['kota'] ?? $source['master']['kota'] ?? $source['kota'] ?? []);
            $placesByKey = $this->importPlaces($source['pkl'] ?? [], $sourceName);
            $studentsByNpm = $this->importUsersAndStudents($source['users'] ?? [], $studyProgram);
            $this->importPlaceStudentsAsEnrollments($placesByKey, $studentsByNpm, $studyProgram, $period);
            $this->importCheckIns($source['mon_pkl'] ?? [], $studentsByNpm, $studyProgram, $period, $sourceName);
            $this->validateMonUser($source['mon_user'] ?? [], $source['mon_pkl'] ?? []);
        };

        if ($this->option('dry-run')) {
            try {
                DB::transaction(function () use ($callback): void {
                    $callback();
                    throw new DryRunRollback();
                });
            } catch (DryRunRollback) {
                // Expected rollback after parsing and validating the import.
            }
        } else {
            DB::transaction($callback);
        }

        return $this->finish();
    }

    private function importStudyProgram(): StudyProgram
    {
        $program = StudyProgram::updateOrCreate(
            ['code' => (string) $this->option('study-program-code')],
            [
                'name' => (string) $this->option('study-program-name'),
                'is_active' => true,
            ],
        );

        return $program;
    }

    private function importPeriod(): InternshipPeriod
    {
        return InternshipPeriod::updateOrCreate(
            [
                'name' => (string) $this->option('period-name'),
                'academic_year' => (string) $this->option('academic-year'),
                'semester' => (string) $this->option('semester'),
                'batch' => (string) $this->option('batch'),
            ],
            [
                'is_active' => true,
                'is_locked' => false,
            ],
        );
    }

    private function importCities(array $cities): void
    {
        foreach ($cities as $key => $city) {
            if (! $city) {
                continue;
            }

            $name = is_array($city) ? ($city['name'] ?? $city['nama'] ?? null) : $city;

            if (! $name) {
                continue;
            }

            City::updateOrCreate(
                ['name' => trim((string) $name)],
                [
                    'legacy_index' => is_int($key) ? $key : null,
                    'legacy_key' => is_string($key) ? $key : null,
                ],
            );

            $this->report['counts']['cities']++;
        }
    }

    private function importPlaces(array $places, string $sourceName): array
    {
        $imported = [];

        foreach ($places as $key => $feature) {
            $properties = $feature['properties'] ?? [];
            $coordinates = $feature['geometry']['coordinates'] ?? [];
            [$longitude, $latitude] = $this->pointCoordinates($coordinates, "pkl:{$key}");

            $city = $this->cityByName($properties['kota'] ?? null);
            $name = trim((string) ($properties['instansi'] ?? "Legacy place {$key}"));

            $place = InternshipPlace::updateOrCreate(
                ['legacy_firebase_key' => (string) $key],
                [
                    'legacy_source_file' => $sourceName,
                    'city_id' => $city?->id,
                    'name' => $name,
                    'address' => $properties['alamat'] ?? null,
                    'field_supervisor_name' => $properties['pemb_lap'] ?? null,
                    'field_supervisor_phone' => $properties['pemb_hp'] ?? null,
                    'contact_student_phone' => $properties['hp_mhs'] ?? null,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'visited' => ((int) ($properties['visited'] ?? 0)) === 1,
                    'legacy_created_at' => $this->timestamp($properties['time'] ?? null),
                ],
            );

            $this->syncPostgisLocation($place);
            $imported[(string) $key] = $place;
            $this->report['counts']['internship_places']++;
        }

        return $imported;
    }

    private function importUsersAndStudents(array $users, StudyProgram $studyProgram): array
    {
        $students = [];

        foreach ($users as $uid => $legacyUser) {
            $npm = trim((string) ($legacyUser['npm'] ?? ''));

            if ($npm === '') {
                $this->report['failed'][] = ['path' => "users/{$uid}", 'reason' => 'missing npm'];
                continue;
            }

            $email = strtolower(trim((string) ($legacyUser['email'] ?? ''))) ?: "{$npm}@legacy.monpkl.local";
            $name = trim((string) ($legacyUser['nama'] ?? $legacyUser['name'] ?? $npm));
            $user = $this->userForLegacyStudent((string) $uid, $email, $npm, $name, $legacyUser['photoURL'] ?? null);

            $student = Student::updateOrCreate(
                ['npm' => $npm],
                [
                    'user_id' => $user->id,
                    'study_program_id' => $studyProgram->id,
                    'full_name' => $name,
                ],
            );

            $students[$npm] = $student;
            $this->report['counts']['users']++;
            $this->report['counts']['students']++;
        }

        return $students;
    }

    private function userForLegacyStudent(string $uid, string $email, string $npm, string $name, ?string $avatarUrl): User
    {
        $user = User::query()->where('firebase_uid', $uid)->first();

        if ($user) {
            $updates = [
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'role' => 'mahasiswa',
            ];

            if (! $user->email) {
                $updates['email'] = $email;
            }

            $user->update([
                ...$updates,
            ]);

            return $user;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user && Student::query()->where('user_id', $user->id)->where('npm', '!=', $npm)->exists()) {
            $this->report['warnings'][] = [
                'path' => "users/{$uid}",
                'reason' => 'email already belongs to another NPM; synthetic legacy email used',
                'original_email' => $email,
                'synthetic_email' => "{$npm}@legacy.monpkl.local",
            ];

            $email = "{$npm}@legacy.monpkl.local";
            $user = User::query()->where('email', $email)->first();
        }

        if ($user) {
            $user->update([
                'name' => $name,
                'firebase_uid' => $uid,
                'avatar_url' => $avatarUrl,
                'role' => 'mahasiswa',
            ]);

            return $user;
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'firebase_uid' => $uid,
            'avatar_url' => $avatarUrl,
            'role' => 'mahasiswa',
            'password' => Hash::make(Str::random(32)),
        ]);
    }

    private function importPlaceStudentsAsEnrollments(
        array $placesByKey,
        array &$studentsByNpm,
        StudyProgram $studyProgram,
        InternshipPeriod $period,
    ): void {
        foreach ($placesByKey as $place) {
            $legacyPlace = $this->readPlaceFeatureFromReportSource($place->legacy_firebase_key);
            $members = $legacyPlace['properties']['mhs'] ?? [];

            foreach ($members as $member) {
                $npm = trim((string) ($member['npm'] ?? ''));

                if ($npm === '') {
                    continue;
                }

                $student = $studentsByNpm[$npm] ??= Student::updateOrCreate(
                    ['npm' => $npm],
                    [
                        'study_program_id' => $studyProgram->id,
                        'full_name' => trim((string) ($member['nama'] ?? $npm)),
                    ],
                );

                InternshipEnrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'study_program_id' => $studyProgram->id,
                        'internship_period_id' => $period->id,
                    ],
                    [
                        'internship_place_id' => $place->id,
                        'field_supervisor' => $place->field_supervisor_name,
                        'field_supervisor_phone' => $place->field_supervisor_phone,
                        'contact_student_phone' => $place->contact_student_phone,
                        'status' => 'active',
                        'legacy_source_file' => basename((string) $this->report['source_file']),
                        'legacy_period_label' => (string) $this->option('legacy-period-label'),
                    ],
                );

                $this->report['counts']['enrollments']++;
            }
        }
    }

    private function importCheckIns(
        array $checkIns,
        array &$studentsByNpm,
        StudyProgram $studyProgram,
        InternshipPeriod $period,
        string $sourceName,
    ): void {
        foreach ($checkIns as $key => $feature) {
            $properties = $feature['properties'] ?? [];
            $npm = trim((string) ($properties['npm'] ?? ''));

            if ($npm === '') {
                $this->report['failed'][] = ['path' => "mon_pkl/{$key}", 'reason' => 'missing npm'];
                continue;
            }

            $student = $studentsByNpm[$npm] ??= Student::updateOrCreate(
                ['npm' => $npm],
                [
                    'study_program_id' => $studyProgram->id,
                    'full_name' => trim((string) ($properties['nama'] ?? $npm)),
                ],
            );

            $place = $this->placeForCheckIn($properties['instansi'] ?? null, $feature, $sourceName);
            $enrollment = InternshipEnrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'study_program_id' => $studyProgram->id,
                    'internship_period_id' => $period->id,
                ],
                [
                    'internship_place_id' => $place?->id,
                    'status' => 'active',
                    'legacy_source_file' => $sourceName,
                    'legacy_period_label' => (string) $this->option('legacy-period-label'),
                ],
            );

            [$office, $studentPoint] = $this->lineCoordinates($feature['geometry']['coordinates'] ?? [], "mon_pkl:{$key}");
            $checkedAt = $this->timestamp($properties['time'] ?? null) ?? now();

            CheckIn::updateOrCreate(
                ['legacy_firebase_key' => (string) $key],
                [
                    'legacy_source_file' => $sourceName,
                    'internship_enrollment_id' => $enrollment->id,
                    'type' => (string) ($properties['keterangan'] ?? 'Tidak Diketahui'),
                    'note' => $properties['catatan'] ?? null,
                    'checked_at' => $checkedAt,
                    'student_latitude' => $studentPoint['lat'] ?? null,
                    'student_longitude' => $studentPoint['lng'] ?? null,
                    'office_latitude' => $office['lat'] ?? $place?->latitude,
                    'office_longitude' => $office['lng'] ?? $place?->longitude,
                    'distance_meters' => $this->distanceMeters($office, $studentPoint),
                    'device_info' => $properties['device'] ?? null,
                    'source_url' => $properties['url'] ?? null,
                    'source_photo_url' => $properties['imgURL'] ?? null,
                    'legacy_geojson_type' => $feature['type'] ?? null,
                ],
            );

            $this->report['counts']['check_ins']++;
        }
    }

    private function placeForCheckIn(?string $name, array $feature, string $sourceName): ?InternshipPlace
    {
        if (! $name) {
            return null;
        }

        $place = InternshipPlace::query()->whereRaw('lower(name) = ?', [strtolower($name)])->first();

        if ($place) {
            return $place;
        }

        [$office] = $this->lineCoordinates($feature['geometry']['coordinates'] ?? [], 'mon_pkl:place-fallback');

        $place = InternshipPlace::create([
            'legacy_source_file' => $sourceName,
            'name' => $name,
            'latitude' => $office['lat'] ?? null,
            'longitude' => $office['lng'] ?? null,
        ]);

        $this->syncPostgisLocation($place);

        return $place;
    }

    private function validateMonUser(array $monUser, array $monPkl): void
    {
        $mainByNpm = [];

        foreach ($monPkl as $feature) {
            $npm = $feature['properties']['npm'] ?? null;

            if ($npm) {
                $mainByNpm[$npm] = ($mainByNpm[$npm] ?? 0) + 1;
            }
        }

        foreach ($monUser as $npm => $records) {
            $count = is_array($records) ? count($records) : 0;
            $this->report['counts']['mon_user_check_ins'] += $count;

            if (($mainByNpm[$npm] ?? 0) !== $count) {
                $this->report['mon_user_validation'][] = [
                    'npm' => (string) $npm,
                    'mon_pkl' => $mainByNpm[$npm] ?? 0,
                    'mon_user' => $count,
                ];
            }
        }
    }

    private function readJson(string $path): array
    {
        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function resolvePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function cityByName(?string $name): ?City
    {
        if (! $name) {
            return null;
        }

        return City::firstOrCreate(['name' => trim($name)]);
    }

    private function pointCoordinates(mixed $coordinates, string $path): array
    {
        if (! is_array($coordinates) || count($coordinates) < 2) {
            $this->report['invalid_coordinates'][] = ['path' => $path, 'coordinates' => $coordinates];

            return [null, null];
        }

        $lng = $this->numericCoordinate($coordinates[0] ?? null);
        $lat = $this->numericCoordinate($coordinates[1] ?? null);

        if (! $this->validLatLng($lat, $lng)) {
            $this->report['invalid_coordinates'][] = ['path' => $path, 'coordinates' => $coordinates];

            return [null, null];
        }

        return [$lng, $lat];
    }

    private function lineCoordinates(mixed $coordinates, string $path): array
    {
        if (! is_array($coordinates) || count($coordinates) < 2) {
            $this->report['invalid_coordinates'][] = ['path' => $path, 'coordinates' => $coordinates];

            return [null, null];
        }

        [$officeLng, $officeLat] = $this->pointCoordinates($coordinates[0], "{$path}:office");
        [$studentLng, $studentLat] = $this->pointCoordinates($coordinates[1], "{$path}:student");

        return [
            $officeLat !== null ? ['lat' => $officeLat, 'lng' => $officeLng] : null,
            $studentLat !== null ? ['lat' => $studentLat, 'lng' => $studentLng] : null,
        ];
    }

    private function numericCoordinate(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function validLatLng(?float $lat, ?float $lng): bool
    {
        return $lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    private function timestamp(mixed $value): ?Carbon
    {
        if (! is_numeric($value)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) floor(((float) $value) / 1000));
    }

    private function distanceMeters(?array $from, ?array $to): ?int
    {
        if (! $from || ! $to) {
            return null;
        }

        $earthRadius = (int) config('monpkl.distance.earth_radius_meters');
        $lat1 = deg2rad($from['lat']);
        $lat2 = deg2rad($to['lat']);
        $deltaLat = deg2rad($to['lat'] - $from['lat']);
        $deltaLng = deg2rad($to['lng'] - $from['lng']);

        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function syncPostgisLocation(InternshipPlace $place): void
    {
        if (DB::getDriverName() !== 'pgsql' || $place->latitude === null || $place->longitude === null) {
            return;
        }

        DB::table('internship_places')
            ->where('id', $place->id)
            ->update([
                'location' => DB::raw("ST_SetSRID(ST_MakePoint({$place->longitude}, {$place->latitude}), 4326)::geography"),
            ]);
    }

    private function readPlaceFeatureFromReportSource(string $key): array
    {
        static $source;

        $source ??= $this->readJson((string) $this->report['source_file']);

        return $source['pkl'][$key] ?? [];
    }

    private function finish(): int
    {
        $path = 'import-reports/firebase-import-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Firebase import finished.');
        $this->table(['Metric', 'Count'], collect($this->report['counts'])->map(fn ($count, $metric) => [$metric, $count])->all());
        $this->info('Report written to '.Storage::disk('local')->path($path));

        if ($this->report['mon_user_validation']) {
            $this->warn('mon_user validation mismatches found: '.count($this->report['mon_user_validation']));
        }

        if ($this->report['failed']) {
            $this->warn('Failed rows: '.count($this->report['failed']));
        }

        return self::SUCCESS;
    }
}

class DryRunRollback extends \RuntimeException
{
}
