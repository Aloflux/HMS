<x-guest-layout>
    <div class="flex flex-col items-center text-center">
        <span class="inline-flex h-14 w-14 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
            <x-icon name="bx-envelope" class="text-3xl" />
        </span>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">Check your inbox</h1>
        <p class="mt-2 text-sm text-slate-600">
            We sent a verification link to
            <span class="font-semibold text-slate-900">{{ auth()->user()->email }}</span>.
            Open it to confirm your email and continue setting up {{ auth()->user()->facility?->name ?? 'your account' }}.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-5 flex items-start gap-2 rounded-md bg-brand-50 px-4 py-3 text-sm font-medium text-brand-900">
            <x-icon name="bx-check-circle" class="mt-0.5 text-lg" />
            <span>A new link is on its way. Use the most recent email.</span>
        </div>
    @endif

    <ul class="mt-5 space-y-2 rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <li class="flex items-start gap-2"><x-icon name="bx-time-five" class="mt-0.5 text-base text-slate-400" />The link expires after {{ config('auth.verification.expire', 60) }} minutes.</li>
        <li class="flex items-start gap-2"><x-icon name="bx-filter-alt" class="mt-0.5 text-base text-slate-400" />Not there? Check your spam or promotions folder.</li>
    </ul>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                <x-icon name="bx-send" class="text-lg" />
                Resend link
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900">
                <x-icon name="bx-log-out" class="text-lg" />
                Wrong email? Sign out
            </button>
        </form>
    </div>
</x-guest-layout>
