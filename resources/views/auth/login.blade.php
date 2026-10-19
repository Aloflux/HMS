<x-guest-layout max-width="sm:max-w-4xl">
    <div class="mb-6">
        <h1 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
            <x-icon name="bx-log-in-circle" class="text-2xl text-brand-700" />
            Sign in to AfyaDesk
        </h1>
        <p class="mt-1 text-sm text-slate-600">Use the account issued for your station. Shared computers sign out after a period of inactivity.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            @if (Route::has('password.request'))
                <a class="text-sm text-slate-600 underline hover:text-slate-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3 inline-flex items-center gap-2">
                <x-icon name="bx-log-in" class="text-lg" />
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    @if ($demoAccounts !== [])
        <section class="mt-8 border-t border-slate-200 pt-6">
            <h2 class="text-sm font-semibold text-slate-900">Sample accounts</h2>
            <p class="mt-1 text-sm text-slate-600">Click an account to fill the email and password. The shared password is {{ $demoPassword }}.</p>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ($demoAccounts as $account)
                    <button
                        type="button"
                        class="demo-account rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-start hover:border-brand-500 hover:bg-brand-50"
                        data-demo-email="{{ $account['email'] }}"
                        data-demo-password="{{ $demoPassword }}"
                    >
                        <span class="flex items-center gap-2 text-sm font-medium text-slate-900">
                            <x-icon name="bx-user" class="text-base text-brand-700" />
                            {{ $account['role'] }}
                        </span>
                        <span class="block text-xs text-slate-500">{{ $account['facility'] }}</span>
                        <span class="block text-xs text-brand-700">{{ $account['email'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif
</x-guest-layout>
