<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(config('services.google.enabled'), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(config('services.google.enabled'), 404);

        $googleUser = Socialite::driver('google')->user();
        $email = strtolower((string) $googleUser->getEmail());

        if (! $this->isAllowedEmailDomain($email)) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Login Google hanya diizinkan untuk domain Universitas Lampung.',
                ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => 'mahasiswa',
            ]);
        }

        Auth::login($user, remember: true);

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
}
