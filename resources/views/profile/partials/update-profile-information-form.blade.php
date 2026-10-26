<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="grid gap-4 rounded-2xl bg-white p-6 shadow-card">
    @csrf
    @method('patch')

    <div>
        <h2 class="font-bold text-slate-900">Account</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $user->accessSummary() }}. The name and email shown at this station.</p>
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
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Your email address is unverified.') }}
                <button form="send-verification" class="font-medium text-brand-700 underline">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
            </p>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-2 text-sm font-medium text-brand-800">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
            @endif
        @endif
    </div>

    <div>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
