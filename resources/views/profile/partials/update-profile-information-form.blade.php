<section>
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

        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="relative">
                @php
                    $photoUrl = $user->profile_photo_url ?? null;
                    $initials = collect(explode(' ', $user->name))->map(fn ($part) => mb_substr($part, 0, 1))->join('');
                @endphp
                <div id="profilePhotoPreviewContainer" class="w-20 h-20 rounded-full border border-navy-200 flex items-center justify-center overflow-hidden bg-navy-50 text-navy-900 font-semibold" data-initials="{{ $initials ?: 'U' }}">
                    @if ($photoUrl)
                        <img id="profilePhotoPreview" src="{{ $photoUrl }}" alt="Profile photo" class="w-full h-full object-cover">
                    @else
                        <span id="profilePhotoInitials" class="text-lg">{{ $initials ?: 'U' }}</span>
                    @endif
                </div>
            </div>

            <div class="flex-1 space-y-2">
                <div>
                    <x-input-label for="profile_photo" :value="__('Profile Photo')" />
                    <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-600" />
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, or JPEG up to 5 MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" id="profilePhotoRemoveButton" class="text-sm text-red-600 hover:underline {{ $photoUrl ? '' : 'hidden' }}">
                        {{ __('Remove current photo') }}
                    </button>
                    <span id="profilePhotoRemovedBadge" class="hidden text-xs text-gray-500">{{ __('Photo will be removed') }}</span>
                </div>
            </div>
        </div>

        <input type="hidden" name="remove_profile_photo" id="remove_profile_photo" value="0" />

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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('profile_photo');
            const previewContainer = document.getElementById('profilePhotoPreviewContainer');
            const removeInput = document.getElementById('remove_profile_photo');
            const removeButton = document.getElementById('profilePhotoRemoveButton');
            const removedBadge = document.getElementById('profilePhotoRemovedBadge');

            if (fileInput) {
                fileInput.addEventListener('change', (event) => {
                    const [file] = event.target.files;
                    if (!file) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        let previewImage = document.getElementById('profilePhotoPreview');
                        let initialsEl = document.getElementById('profilePhotoInitials');

                        if (!previewImage) {
                            previewContainer.innerHTML = '';
                            previewImage = document.createElement('img');
                            previewImage.id = 'profilePhotoPreview';
                            previewImage.className = 'w-full h-full object-cover';
                            previewContainer.appendChild(previewImage);
                        }

                        previewImage.src = e.target.result;
                        previewImage.classList.remove('hidden');

                        if (initialsEl) {
                            initialsEl.classList.add('hidden');
                        }

                        removeInput.value = '0';
                        removeButton?.classList.remove('hidden');
                        removedBadge?.classList.add('hidden');
                    };

                    reader.readAsDataURL(file);
                });
            }

            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    removeInput.value = '1';
                    const existingPreview = document.getElementById('profilePhotoPreview');
                    const existingInitials = document.getElementById('profilePhotoInitials');

                    if (existingPreview) {
                        existingPreview.remove();
                    }

                    if (existingInitials) {
                        existingInitials.classList.remove('hidden');
                    } else {
                        const initialsSpan = document.createElement('span');
                        initialsSpan.id = 'profilePhotoInitials';
                        initialsSpan.className = 'text-lg';
                        initialsSpan.textContent = previewContainer.dataset.initials || 'U';
                        previewContainer.innerHTML = '';
                        previewContainer.appendChild(initialsSpan);
                    }
                    if (fileInput) {
                        fileInput.value = '';
                    }
                    removeButton.classList.add('hidden');
                    removedBadge?.classList.remove('hidden');
                });
            }
        });
    </script>
@endpush
