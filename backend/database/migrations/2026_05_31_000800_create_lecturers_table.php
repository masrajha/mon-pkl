<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('nip')->nullable()->unique();
            $table->string('nidn')->nullable()->unique();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['study_program_id', 'status']);
            $table->index(['name', 'email']);
        });

        Schema::table('internship_enrollments', function (Blueprint $table) {
            $table->foreignId('lecturer_supervisor_id')
                ->nullable()
                ->after('internship_place_id')
                ->constrained('lecturers')
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('role', 'dosen')
            ->orderBy('id')
            ->get(['id', 'name', 'email'])
            ->each(function ($user): void {
                DB::table('lecturers')->insert([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        DB::table('lecturers')
            ->get(['id', 'user_id', 'name', 'email'])
            ->each(function ($lecturer): void {
                DB::table('internship_enrollments')
                    ->whereNull('lecturer_supervisor_id')
                    ->where(function ($query) use ($lecturer): void {
                        $query->where('lecturer_supervisor_user_id', $lecturer->user_id)
                            ->orWhere('lecturer_supervisor', $lecturer->name)
                            ->orWhere('lecturer_supervisor', $lecturer->email);
                    })
                    ->update(['lecturer_supervisor_id' => $lecturer->id]);
            });
    }

    public function down(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lecturer_supervisor_id');
        });

        Schema::dropIfExists('lecturers');
    }
};
