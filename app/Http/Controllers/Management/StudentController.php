<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = Student::query()->with(['user', 'studyProgram']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('npm', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($query) => $query->where('email', 'like', '%'.$search.'%'));
            });
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        return view('management.students.index', [
            'students' => $this->applyTableSort($query, $request, ['npm', 'full_name'], 'full_name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'users' => User::query()->where('role', 'mahasiswa')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $this->validated($request);
            $student = Student::query()->create($this->studentPayload($data));
            $this->syncLoginAccount($student, $data);
        });

        return back()->with('status', 'Mahasiswa dan akun login berhasil diproses.');
    }

    public function edit(Student $student): View
    {
        return view('management.students.edit', [
            'student' => $student,
            'users' => User::query()->where('role', 'mahasiswa')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        DB::transaction(function () use ($request, $student): void {
            $data = $this->validated($request, $student);
            $student->update($this->studentPayload($data));
            $this->syncLoginAccount($student, $data);
        });

        return redirect()->route('management.students.index')->with('status', 'Mahasiswa berhasil diperbarui.');
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'account_mode' => ['nullable', Rule::in(['auto', 'link', 'none'])],
            'user_id' => ['nullable', 'required_if:account_mode,link', 'exists:users,id', Rule::unique('students')->ignore($student)],
            'login_email' => ['nullable', 'required_if:account_mode,auto', 'email', 'max:255'],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'npm' => ['required', 'string', 'max:30', Rule::unique('students')->ignore($student)],
            'full_name' => ['required', 'string', 'max:255'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $data['account_mode'] ??= filled($data['user_id'] ?? null) ? 'link' : 'auto';

        if ($data['account_mode'] === 'auto' && blank($data['login_email'] ?? null)) {
            throw ValidationException::withMessages(['login_email' => 'Email login wajib diisi untuk membuat akun otomatis.']);
        }

        return $data;
    }

    private function studentPayload(array $data): array
    {
        $email = match ($data['account_mode'] ?? 'auto') {
            'auto' => $data['login_email'] ?? null,
            'none' => $data['student_email'] ?? null,
            default => null,
        };

        return [
            'user_id' => $data['user_id'] ?? null,
            'study_program_id' => $data['study_program_id'] ?? null,
            'npm' => $data['npm'],
            'full_name' => $data['full_name'],
            'student_email' => $email,
            'phone' => $data['phone'] ?? null,
        ];
    }

    private function syncLoginAccount(Student $student, array $data): void
    {
        $mode = $data['account_mode'] ?? 'auto';

        if ($mode === 'none') {
            $student->update(['user_id' => null]);

            return;
        }

        if ($mode === 'link') {
            $user = User::query()->whereKey($data['user_id'] ?? null)->firstOrFail();
            if ($user->role !== 'mahasiswa') {
                throw ValidationException::withMessages(['user_id' => 'Akun yang ditautkan harus berperan Mahasiswa.']);
            }
            $student->update([
                'user_id' => $user->id,
                'student_email' => $user->email,
            ]);

            return;
        }

        $email = Str::lower(trim((string) ($data['login_email'] ?? '')));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if ($user->role !== 'mahasiswa') {
                throw ValidationException::withMessages(['login_email' => 'Email sudah digunakan oleh akun dengan role lain.']);
            }
            if (Student::query()->where('user_id', $user->id)->whereKeyNot($student->id)->exists()) {
                throw ValidationException::withMessages(['login_email' => 'Email sudah tertaut ke mahasiswa lain.']);
            }
            $user->update(['name' => $data['full_name'], 'role' => 'mahasiswa']);
        } else {
            $user = User::query()->create([
                'name' => $data['full_name'],
                'email' => $email,
                'role' => 'mahasiswa',
                'password' => Str::password(16),
            ]);
        }

        $student->update([
            'user_id' => $user->id,
            'student_email' => $user->email,
        ]);
    }
}
