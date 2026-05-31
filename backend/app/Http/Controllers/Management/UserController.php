<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('management.users.index', [
            'users' => User::query()->orderBy('role')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::query()->create($this->validated($request));

        return back()->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('management.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $user->update($this->validated($request, $user));

        return redirect()->route('management.users.index')->with('status', 'User berhasil diperbarui.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'dosen', 'mahasiswa'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
