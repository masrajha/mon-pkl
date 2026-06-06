<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_remove_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'User Avatar',
                'email' => $user->email,
                'avatar_photo' => $this->fakePng('avatar.png'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar_url);
        $this->assertStringStartsWith('profile-photos/', $user->avatar_url);

        $path = $user->avatar_url;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->get(route('media.public', ['path' => $path]))
            ->assertOk();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'User Avatar',
                'email' => $user->email,
                'remove_avatar' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->refresh()->avatar_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_upload_profile_photo_for_managed_user(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $managedUser = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($admin)
            ->patch(route('management.users.update', $managedUser), [
                'name' => $managedUser->name,
                'email' => $managedUser->email,
                'role' => $managedUser->role,
                'avatar_photo' => $this->fakePng('managed-avatar.png'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('management.users.index'));

        $managedUser->refresh();

        $this->assertNotNull($managedUser->avatar_url);
        Storage::disk('public')->assertExists($managedUser->avatar_url);

        $this->actingAs($admin)
            ->get(route('management.users.index'))
            ->assertOk()
            ->assertSee(route('media.public', ['path' => $managedUser->avatar_url]));
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
