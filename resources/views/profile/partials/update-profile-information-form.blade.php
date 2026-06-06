<section>
    @php($avatarUrl = \App\Support\PublicStorage::url($user->avatar_url))

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="avatar_photo" :value="__('Foto Profil')" />
            <div class="mt-2 flex items-center gap-4">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="Foto profil {{ $user->name }}" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-xl font-semibold text-blue-700">
                        {{ Str::of($user->name)->substr(0, 1)->upper() }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <input id="avatar_photo" name="avatar_photo" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                    <p class="mt-1 text-xs text-gray-500">Gunakan JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                    @if ($user->avatar_url)
                        <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-blue-600">
                            Hapus foto profil saat ini
                        </label>
                    @endif
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar_photo')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
