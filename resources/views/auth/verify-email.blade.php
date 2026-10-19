<x-guest-layout>
    <div class="mb-6">
        <h1 class="flex items-center gap-2 text-xl font-semibold text-slate-900">
            <x-icon name="bx-envelope" class="text-2xl text-brand-700" />
            Verify your email
        </h1>
        <p class="mt-1 text-sm text-slate-600">Open the link we sent, or ask for another one. You can also sign out and use a different station.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <p class="mb-4 text-sm font-medium text-brand-800">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </p>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900">
                <x-icon name="bx-log-out" class="text-lg" />
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
