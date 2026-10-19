<form method="post" action="{{ route('password.update') }}" class="grid gap-4 rounded-lg bg-white p-6 shadow-sm">
    @csrf
    @method('put')

    <div>
        <h2 class="flex items-center gap-2 font-semibold">
            <x-icon name="bx-lock-alt" class="text-lg text-brand-700" />
            Password
        </h2>
        <p class="mt-1 text-sm text-slate-500">Use a long password that you do not reuse at another station.</p>
    </div>

    <div>
        <x-input-label for="update_password_current_password" :value="__('Current Password')" />
        <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password" :value="__('New Password')" />
        <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
        <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <div>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
