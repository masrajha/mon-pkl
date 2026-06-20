<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(config('services.google.enabled'), 404);

        return $this->googleProvider()->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(config('services.google.enabled'), 404);

        $googleUser = $this->googleProvider()->user();
        $email = strtolower((string) $googleUser->getEmail());

        $isFieldSupervisorEmail = $this->isFieldSupervisorEmail($email);

        if (! $this->isAllowedEmailDomain($email) && ! $isFieldSupervisorEmail) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Login Google hanya diizinkan untuk domain Universitas Lampung atau email pembimbing lapangan yang terdaftar.',
                ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $updates = [
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ];

            if ($isFieldSupervisorEmail && $user->role === 'mahasiswa' && ! $user->student()->exists()) {
                $updates['role'] = 'pembimbing_lapangan';
            }

            $user->update($updates);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => $isFieldSupervisorEmail ? 'pembimbing_lapangan' : 'mahasiswa',
            ]);
        }

        Auth::login($user, remember: true);

        if ($user->hasRole('pembimbing_lapangan')) {
            return redirect()->intended(route('field-supervisor.index', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function isAllowedEmailDomain(string $email): bool
    {
        $domain = Str::after($email, '@');

        if ($domain === $email || $domain === '') {
            return false;
        }

        foreach (config('services.google.allowed_domains', []) as $allowedDomain) {
            $allowedDomain = strtolower($allowedDomain);

            if (Str::startsWith($allowedDomain, '*.')) {
                $suffix = Str::after($allowedDomain, '*.');

                if (Str::endsWith($domain, '.'.$suffix)) {
                    return true;
                }

                continue;
            }

            if ($domain === $allowedDomain) {
                return true;
            }
        }

        return false;
    }

    private function isFieldSupervisorEmail(string $email): bool
    {
        return InternshipEnrollment::query()
            ->whereRaw('LOWER(field_supervisor_email) = ?', [Str::lower(trim($email))])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();
    }

    private function googleProvider(): Provider
    {
        $provider = Socialite::driver('google');

        if (config('services.google.stateless')) {
            $provider->stateless();
        }

        return $provider;
    }
}
