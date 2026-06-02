<?php

use App\Models\InternshipPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('rule_key', 50)->default('kerja_praktik')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $defaultProgramId = DB::table('programs')->insertGetId([
            'code' => 'KP',
            'name' => 'Kerja Praktik',
            'description' => 'Program Kerja Praktik dengan rule operasional default.',
            'rule_key' => 'kerja_praktik',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['code' => 'MAGANG', 'name' => 'Magang'],
            ['code' => 'RISET', 'name' => 'Riset'],
            ['code' => 'STUDI-INDEPENDEN', 'name' => 'Studi Independen'],
        ] as $program) {
            DB::table('programs')->insert([
                'code' => $program['code'],
                'name' => $program['name'],
                'description' => 'Program MBKM. Sementara memakai rule Kerja Praktik.',
                'rule_key' => 'kerja_praktik',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('internship_periods', function (Blueprint $table): void {
            $table->foreignId('program_id')->nullable()->after('id')->constrained('programs')->nullOnDelete();
        });

        InternshipPeriod::query()->update(['program_id' => $defaultProgramId]);
    }

    public function down(): void
    {
        Schema::table('internship_periods', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_id');
        });

        Schema::dropIfExists('programs');
    }
};
