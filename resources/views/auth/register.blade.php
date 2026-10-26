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
                <p class="mt-8 max-w-md text-3xl font-semibold leading-tight lg:mt-16 lg:text-4xl">Set up your facility in minutes.</p>
                <p class="mt-4 max-w-md text-sm leading-6 text-brand-100 lg:text-base">Register your clinic or hospital, confirm your email, then walk through a short setup. You become the facility administrator.</p>
            </div>

            <ol class="relative mt-10 hidden gap-4 lg:grid">
                @foreach ([['bx-edit', 'Tell us about your facility and yourself.'], ['bx-envelope', 'Confirm your email from the link we send you.'], ['bx-bed', 'Check your details, wards and beds, then go live.']] as [$icon, $text])
                    <li class="flex items-start gap-3">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white/10 text-sm font-semibold text-brand-100">{{ $loop->iteration }}</span>
                        <span class="text-sm leading-6 text-brand-50">{{ $text }}</span>
                    </li>
                @endforeach
            </ol>
        </aside>

        <main class="flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-14">
            <div class="mx-auto flex w-full max-w-2xl flex-col gap-8">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Register your facility</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">Sign in</a>
                    </p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-6">
                    @csrf

                    <section class="rounded-lg bg-white p-5 shadow-card">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                                <x-icon name="bx-buildings" class="text-xl" />
                            </span>
                            <h2 class="font-bold text-slate-900">Your facility</h2>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-input-label for="facility_name" value="Facility name" />
                                <x-text-input id="facility_name" class="mt-1 block w-full" name="facility_name" :value="old('facility_name')" placeholder="Hope Medical Centre" required autofocus />
                                <x-input-error :messages="$errors->get('facility_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="facility_kind" value="Type" />
                                <select id="facility_kind" name="facility_kind" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none" required>
                                    @foreach ($kinds as $value => $label)
                                        <option value="{{ $value }}" @selected(old('facility_kind', 'clinic') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('facility_kind')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="district" value="District" />
                                <x-text-input id="district" class="mt-1 block w-full" name="district" :value="old('district')" placeholder="Kampala" required />
                                <x-input-error :messages="$errors->get('district')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-5 shadow-card">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                                <x-icon name="bx-user" class="text-xl" />
                            </span>
                            <div>
                                <h2 class="font-bold text-slate-900">Your account</h2>
                                <p class="text-sm text-slate-500">You will be the facility administrator.</p>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="name" value="Full name" />
                                <x-text-input id="name" class="mt-1 block w-full" name="name" :value="old('name')" required autocomplete="name" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" value="Phone (optional)" />
                                <x-text-input id="phone" class="mt-1 block w-full" type="tel" name="phone" :value="old('phone')" autocomplete="tel" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="email" value="Work email" />
                                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                                <p class="mt-1 text-xs text-slate-500">We will send a verification link here.</p>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="password" value="Password" />
                                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" value="Confirm password" />
                                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">Your facility starts with standard departments, stores and two wards. You can change them during setup.</p>
                        <x-primary-button>
                            Create facility
                            <x-icon name="bx-right-arrow-alt" class="text-lg" />
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-guest-layout>
