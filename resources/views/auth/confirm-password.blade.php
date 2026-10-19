<x-guest-layout>
    <div class="mb-6">
        <h1 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
            <x-icon name="bx-lock" class="text-2xl text-brand-700" />
            Confirm your password
        </h1>
        <p class="mt-1 text-sm text-slate-600">Enter the password for this station before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
