<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LecturerController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = Lecturer::query()->with(['user', 'studyProgram']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('nip', 'like', '%'.$search.'%')
                ->orWhere('nidn', 'like', '%'.$search.'%'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('management.lecturers.index', $this->formData() + [
            'lecturers' => $this->applyTableSort($query, $request, ['name', 'nip', 'nidn', 'status'], 'name')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $this->validated($request);
            $lecturer = Lecturer::query()->create($this->lecturerPayload($data));
            $this->syncLoginAccount($lecturer, $data);
        });

        return back()->with('status', 'Dosen dan akun login berhasil diproses.');
    }

    public function edit(Lecturer $lecturer): View
    {
        return view('management.lecturers.edit', $this->formData() + compact('lecturer'));
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        DB::transaction(function () use ($request, $lecturer): void {
            $data = $this->validated($request, $lecturer);
            $lecturer->update($this->lecturerPayload($data));
            $this->syncLoginAccount($lecturer, $data);
        });

        return redirect()->route('management.lecturers.index')->with('status', 'Dosen berhasil diperbarui.');
    }

    private function validated(Request $request, ?Lecturer $lecturer = null): array
    {
        $data = $request->validate([
            'account_mode' => ['nullable', Rule::in(['auto', 'link', 'none'])],
            'user_id' => ['nullable', 'required_if:account_mode,link', Rule::exists('users', 'id'), Rule::unique('lecturers')->ignore($lecturer)],
            'login_email' => ['nullable', 'required_if:account_mode,auto', 'email', 'max:255'],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('lecturers')->ignore($lecturer)],
            'nidn' => ['nullable', 'string', 'max:50', Rule::unique('lecturers')->ignore($lecturer)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['account_mode'] ??= filled($data['user_id'] ?? null) ? 'link' : 'auto';

        if ($data['account_mode'] === 'auto' && blank($data['login_email'] ?? null)) {
            throw ValidationException::withMessages(['login_email' => 'Email login wajib diisi untuk membuat akun otomatis.']);
        }

        return $data;
    }

    private function formData(): array
    {
        return [
            'users' => User::query()->where('role', 'dosen')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function lecturerPayload(array $data): array
    {
        $email = match ($data['account_mode'] ?? 'auto') {
            'auto' => $data['login_email'] ?? null,
            'none' => $data['email'] ?? null,
            default => null,
        };

        return [
            'user_id' => $data['user_id'] ?? null,
            'study_program_id' => $data['study_program_id'] ?? null,
            'name' => $data['name'],
            'email' => $email,
            'nip' => $data['nip'] ?? null,
            'nidn' => $data['nidn'] ?? null,
            'status' => $data['status'],
        ];
    }

    private function syncLoginAccount(Lecturer $lecturer, array $data): void
    {
        $mode = $data['account_mode'] ?? 'auto';

        if ($mode === 'none') {
            $lecturer->update(['user_id' => null]);

            return;
        }

        if ($mode === 'link') {
            $user = User::query()->whereKey($data['user_id'] ?? null)->firstOrFail();
            if ($user->role !== 'dosen') {
                throw ValidationException::withMessages(['user_id' => 'Akun yang ditautkan harus berperan Dosen.']);
            }
            $lecturer->update([
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return;
        }

        $email = Str::lower(trim((string) ($data['login_email'] ?? '')));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if ($user->role !== 'dosen') {
                throw ValidationException::withMessages(['login_email' => 'Email sudah digunakan oleh akun dengan role lain.']);
            }
            if (Lecturer::query()->where('user_id', $user->id)->whereKeyNot($lecturer->id)->exists()) {
                throw ValidationException::withMessages(['login_email' => 'Email sudah tertaut ke dosen lain.']);
            }
            $user->update(['name' => $data['name'], 'role' => 'dosen']);
        } else {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'role' => 'dosen',
                'password' => Str::password(16),
            ]);
        }

        $lecturer->update([
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
