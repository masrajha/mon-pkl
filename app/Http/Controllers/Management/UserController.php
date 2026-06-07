<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ManagesUserAvatar;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    use InteractsWithTableControls;
    use ManagesUserAvatar;

    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('role', 'like', '%'.$search.'%'));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        return view('management.users.index', [
            'users' => $this->applyTableSort($query, $request, ['name', 'email', 'role'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedRole' => $request->string('role')->toString(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            [$userData, $profileData] = $this->validated($request);
            $user = new User($userData);
            $this->applyAvatarInput($request, $user);
            $user->save();
            $this->syncRoleProfile($user, $profileData);
        });

        return back()->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $user->load(['student', 'lecturer.studyProgram']);

        return view('management.users.edit', [
            'user' => $user,
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            [$userData, $profileData] = $this->validated($request, $user);
            $user->fill($userData);
            $this->applyAvatarInput($request, $user);
            $user->save();
            $this->syncRoleProfile($user, $profileData);
        });

        return redirect()->route('management.users.index')->with('status', 'User berhasil diperbarui.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in($user?->role === 'pembimbing_lapangan'
                ? ['admin', 'dosen', 'mahasiswa', 'pembimbing_lapangan']
                : ['admin', 'dosen', 'mahasiswa'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'avatar_photo' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'student_npm' => ['nullable', 'required_if:role,mahasiswa', 'string', 'max:30', Rule::unique('students', 'npm')->ignore($user?->student)],
            'student_study_program_id' => ['nullable', 'required_if:role,mahasiswa', 'exists:study_programs,id'],
            'student_phone' => ['nullable', 'string', 'max:50'],
            'lecturer_study_program_id' => ['nullable', 'exists:study_programs,id'],
            'lecturer_nip' => ['nullable', 'string', 'max:50', Rule::unique('lecturers', 'nip')->ignore($user?->lecturer)],
            'lecturer_nidn' => ['nullable', 'string', 'max:50', Rule::unique('lecturers', 'nidn')->ignore($user?->lecturer)],
            'lecturer_status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $profileData = [
            'student' => [
                'npm' => $data['student_npm'] ?? null,
                'study_program_id' => $data['student_study_program_id'] ?? null,
                'student_email' => $data['email'],
                'phone' => $data['student_phone'] ?? null,
            ],
            'lecturer' => [
                'study_program_id' => $data['lecturer_study_program_id'] ?? null,
                'email' => $data['email'],
                'nip' => $data['lecturer_nip'] ?? null,
                'nidn' => $data['lecturer_nidn'] ?? null,
                'status' => $data['lecturer_status'] ?? 'active',
            ],
        ];

        unset(
            $data['avatar_photo'],
            $data['remove_avatar'],
            $data['student_npm'],
            $data['student_study_program_id'],
            $data['student_phone'],
            $data['lecturer_study_program_id'],
            $data['lecturer_nip'],
            $data['lecturer_nidn'],
            $data['lecturer_status'],
        );

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return [$data, $profileData];
    }

    private function syncRoleProfile(User $user, array $profileData): void
    {
        if ($user->role === 'mahasiswa') {
            Student::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'npm' => $profileData['student']['npm'],
                    'full_name' => $user->name,
                    'study_program_id' => $profileData['student']['study_program_id'],
                    'student_email' => $profileData['student']['student_email'] ?: $user->email,
                    'phone' => $profileData['student']['phone'],
                ]
            );
        }

        if ($user->role === 'dosen') {
            Lecturer::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'email' => $profileData['lecturer']['email'] ?: $user->email,
                    'study_program_id' => $profileData['lecturer']['study_program_id'],
                    'nip' => $profileData['lecturer']['nip'],
                    'nidn' => $profileData['lecturer']['nidn'],
                    'status' => $profileData['lecturer']['status'],
                ]
            );
        }
    }
}
