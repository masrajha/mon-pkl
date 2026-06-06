<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Support\PublicStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait ManagesUserAvatar
{
    private function applyAvatarInput(Request $request, User $user): void
    {
        if ($request->boolean('remove_avatar')) {
            $this->deleteLocalAvatar($user);
            $user->avatar_url = null;
        }

        if ($request->hasFile('avatar_photo')) {
            $this->deleteLocalAvatar($user);

            $path = $request->file('avatar_photo')->store('profile-photos', 'public');
            $user->avatar_url = $path;
        }
    }

    private function deleteLocalAvatar(User $user): void
    {
        $path = PublicStorage::path($user->avatar_url);

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
