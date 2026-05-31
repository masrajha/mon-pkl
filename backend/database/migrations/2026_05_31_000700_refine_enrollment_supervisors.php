<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table) {
            $table->foreignId('lecturer_supervisor_user_id')
                ->nullable()
                ->after('internship_place_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('field_supervisor_phone')->nullable()->after('field_supervisor');
        });

        User::query()
            ->where('role', 'dosen')
            ->get(['id', 'name', 'email'])
            ->each(function (User $lecturer): void {
                DB::table('internship_enrollments')
                    ->whereNull('lecturer_supervisor_user_id')
                    ->where(function ($query) use ($lecturer): void {
                        $query->where('lecturer_supervisor', $lecturer->name)
                            ->orWhere('lecturer_supervisor', $lecturer->email);
                    })
                    ->update(['lecturer_supervisor_user_id' => $lecturer->id]);
            });
    }

    public function down(): void
    {
        Schema::table('internship_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lecturer_supervisor_user_id');
            $table->dropColumn('field_supervisor_phone');
        });
    }
};
