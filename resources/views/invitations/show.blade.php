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

        <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-6 grid gap-4">
            @csrf
            <div>
                <x-input-label value="Email" />
                <p class="mt-1 text-sm font-medium text-slate-900">{{ $membership->user->email }}</p>
            </div>
            @if ($state === 'new')
                <div>
                    <x-input-label for="name" value="Your name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $membership->invited_name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
            @else
                <p class="rounded-md bg-slate-50 px-3 py-2.5 text-sm text-slate-600">You already use Tunzuri. Choose a separate password for {{ $membership->facility->name }}; your other facilities keep theirs.</p>
            @endif
            <div>
                <x-input-label for="password" value="Password for {{ $membership->facility->name }}" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required :autofocus="$state !== 'new'" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirm password" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
            </div>
            <p class="text-xs text-slate-500">You sign in with facility code <span class="font-semibold text-slate-700">{{ $membership->facility->code }}</span>, your email and this password.</p>
            <x-primary-button class="justify-center">Accept and sign in</x-primary-button>
        </form>
    @endif
</x-guest-layout>
