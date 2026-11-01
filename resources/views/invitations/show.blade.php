<x-guest-layout>
    @if ($state === 'invalid')
        <h1 class="text-xl font-bold text-slate-900">This invitation cannot be used</h1>
        @if ($facility)
            <p class="mt-2 text-sm text-slate-600">Your invitation to {{ $facility->name }}{{ $facility->district ? ' ('.$facility->district.')' : '' }} has expired. Ask its administrator to send a new one.</p>
        @else
            <p class="mt-2 text-sm text-slate-600">It may have expired, been withdrawn or already been accepted. Ask the facility's administrator to send a new one.</p>
        @endif
        <a href="{{ route('login') }}" class="mt-6 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800">Go to sign in</a>
    @else
        <h1 class="text-xl font-bold text-slate-900">Join {{ $membership->facility->name }}</h1>
        @if ($membership->facility->district)
            <p class="text-sm text-slate-500">{{ $membership->facility->district }}</p>
        @endif
        <p class="mt-2 text-sm text-slate-600">You have been invited to work here with this access: {{ $membership->accessAreas() }}.</p>

        @if ($state === 'new')
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-6 grid gap-4">
                @csrf
                <div>
                    <x-input-label value="Email" />
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $membership->user->email }}</p>
                </div>
                <div>
                    <x-input-label for="name" value="Your name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $membership->invited_name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="Choose a password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                </div>
                <x-primary-button class="justify-center">Accept and sign in</x-primary-button>
            </form>
        @elseif ($state === 'sign-in')
            <p class="mt-4 text-sm text-slate-600">Sign in with your Tunzuri account ({{ $membership->user->email }}) to accept. You will come back here afterwards.</p>
            <a href="{{ route('login') }}" class="mt-6 inline-flex items-center justify-center rounded-md bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-brand hover:bg-brand-800">Sign in to accept</a>
        @elseif ($state === 'other-account')
            <p class="mt-4 text-sm text-slate-600">This invitation is for {{ $membership->user->email }}, but you are signed in with a different account. Sign out, then open the link again.</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <x-primary-button>Sign out</x-primary-button>
            </form>
        @else
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-6">
                @csrf
                <x-primary-button>Accept invitation</x-primary-button>
            </form>
        @endif
    @endif
</x-guest-layout>
