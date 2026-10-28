<x-guest-layout plain>
    <div class="min-h-screen lg:grid lg:grid-cols-[24rem_minmax(0,1fr)]">
        <aside class="relative overflow-hidden bg-brand-900 px-6 py-8 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:px-10 lg:py-12">
            <span class="pointer-events-none absolute -right-6 -bottom-10 hidden text-[11rem] text-white/10 lg:block">
                <x-icon name="bx-plus-medical" />
            </span>

            <div class="relative">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                    <x-icon name="bx-plus-medical" class="text-3xl text-brand-200" />
                    <span class="text-xl font-semibold">Tunzuri</span>
                </a>
                <p class="mt-8 max-w-md text-3xl font-semibold leading-tight lg:mt-16 lg:text-4xl">One record, from the queue to the receipt.</p>
                <p class="mt-4 max-w-md text-sm leading-6 text-brand-100 lg:text-base">Sign in with the account issued for your station. You only see the areas an administrator has opened for you.</p>
            </div>

            <ul class="relative mt-10 hidden gap-4 lg:grid">
                <li class="flex items-start gap-3">
                    <x-icon name="bx-id-card" class="mt-0.5 text-xl text-brand-200" />
                    <span class="text-sm leading-6 text-brand-50">Front office registers patients, runs the queue, and takes payment.</span>
                </li>
                <li class="flex items-start gap-3">
                    <x-icon name="bx-plus-medical" class="mt-0.5 text-xl text-brand-200" />
                    <span class="text-sm leading-6 text-brand-50">The clinical team triages, consults, and covers the wards, maternity, and theatre.</span>
                </li>
                <li class="flex items-start gap-3">
                    <x-icon name="bx-capsule" class="mt-0.5 text-xl text-brand-200" />
                    <span class="text-sm leading-6 text-brand-50">Diagnostics, pharmacy, and stores each keep their own work and their own stock.</span>
                </li>
            </ul>
        </aside>

        <main class="flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-14">
            <div class="mx-auto flex w-full max-w-lg flex-col gap-8">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Sign in</h1>
                    <p class="mt-1 text-sm text-slate-600">Shared computers sign out after a period of inactivity.</p>
                    @if (Route::has('register'))
                        <p class="mt-3 text-sm text-slate-600">
                            New to Tunzuri?
                            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">Register your facility</a>
                        </p>
                    @endif
                </div>

                <x-auth-session-status :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        @if (Route::has('password.request'))
                            <a class="mt-2 inline-block text-sm text-slate-600 underline hover:text-slate-900" href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 shadow-brand">
                        <x-icon name="bx-log-in" class="text-lg" />
                        {{ __('Log in') }}
                    </button>
                </form>

                @if ($demoAccounts !== [])
                    <section class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-4">
                            <h2 class="text-sm font-semibold text-slate-900">Sample accounts</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Choose a person to fill the form. Each of these accounts still signs in with {{ $demoPassword }}.</p>
                        </div>

                        @foreach (collect($demoAccounts)->groupBy('facility') as $facility => $accounts)
                            <div>
                                <h3 class="bg-slate-50 px-4 py-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $facility }}</h3>
                                <div class="flex flex-col">
                                    @foreach ($accounts as $account)
                                        @php
                                            $initials = collect(preg_split('/\s+/', trim($account['name'])))
                                                ->filter()
                                                ->take(2)
                                                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                                ->implode('');
                                        @endphp
                                        <button
                                            type="button"
                                            class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-start transition first:border-t-0 hover:bg-brand-50 aria-pressed:bg-brand-50"
                                            data-demo-email="{{ $account['email'] }}"
                                            data-demo-password="{{ $demoPassword }}"
                                            aria-pressed="false"
                                        >
                                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-800">{{ $initials }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $account['name'] }}</span>
                                                <span class="block truncate text-xs text-slate-500">{{ $account['role'] }}</span>
                                                <span class="block truncate text-xs text-brand-700">{{ $account['email'] }}</span>
                                            </span>
                                            <x-icon name="bx-chevron-right" class="shrink-0 text-xl text-slate-300" />
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>
        </main>
    </div>
</x-guest-layout>
