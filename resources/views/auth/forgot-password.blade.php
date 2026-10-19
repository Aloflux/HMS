<x-guest-layout>
    <div class="mb-6">
        <h1 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
            <x-icon name="bx-lock-open" class="text-2xl text-brand-700" />
            Reset your password
        </h1>
        <p class="mt-1 text-sm text-slate-600">Enter the email for your station. We will send a link to choose a new password.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            <a class="text-sm text-slate-600 underline hover:text-slate-900" href="{{ route('login') }}">Back to sign in</a>
            <x-primary-button class="ms-3">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
