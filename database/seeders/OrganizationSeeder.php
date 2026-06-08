<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $university = Organization::query()->firstOrCreate(
            ['code' => 'UNILA'],
            [
                'type' => 'university',
                'name' => 'Universitas Lampung',
                'is_active' => true,
            ],
        );

        $university->update([
            'parent_id' => null,
            'type' => 'university',
            'name' => 'Universitas Lampung',
            'is_active' => true,
        ]);

        $faculty = Organization::query()->firstOrCreate(
            ['code' => 'FMIPA'],
            [
                'parent_id' => $university->id,
                'type' => 'faculty',
                'name' => 'FMIPA',
                'is_active' => true,
            ],
        );

        $faculty->update([
            'parent_id' => $university->id,
            'type' => 'faculty',
            'name' => 'FMIPA',
            'is_active' => true,
        ]);

        $department = Organization::query()->firstOrCreate(
            ['code' => 'JUR-ILKOM'],
            [
                'parent_id' => $faculty->id,
                'type' => 'department',
                'name' => 'Jurusan Ilmu Komputer',
                'is_active' => true,
            ],
        );

        $department->update([
            'parent_id' => $faculty->id,
            'type' => 'department',
            'name' => 'Jurusan Ilmu Komputer',
            'is_active' => true,
        ]);

        StudyProgram::query()
            ->whereNull('organization_id')
            ->orWhere('organization_id', '!=', $department->id)
            ->update(['organization_id' => $department->id]);
    }
}
