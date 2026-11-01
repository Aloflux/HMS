<x-app-layout>
    @php
        $initials = fn (string $name): string => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
        $trendPeak = max(1, $visits['trend']->max('count'));
        $trendTotal = $visits['trend']->sum('count');
        $occupancy = $beds['inService'] > 0 ? (int) round($beds['occupied'] / $beds['inService'] * 100) : 0;
        $payerTotal = max(1, $visits['byPayer']->sum());
        $typeTotal = max(1, $visits['byType']->sum());
        $rolePeak = max(1, $staff['byRole']->max() ?? 1);
        $stockIssues = $stock['low'] + $stock['expired'] + $stock['expiringSoon'];
        $profile = [
            'Code' => $facility->code,
            'Type' => $kinds[$facility->kind] ?? ucfirst(str_replace('_', ' ', $facility->kind)),
            'District' => $facility->district,
            'Country' => $facility->country,
            'Currency' => $facility->currency,
            'Address' => $facility->address,
            'Phone' => $facility->phone,
            'Email' => $facility->email,
            'MoH code' => $facility->moh_code,
            'Signed up' => $facility->created_at?->format('d M Y'),
            'Went live' => $facility->onboarded_at?->format('d M Y') ?? 'Still setting up',
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('platform.facilities.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-800">
                    <x-icon name="bx-left-arrow-alt" class="text-base" />
                    Facilities
                </a>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <span @class([
                        'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-sm font-bold',
                        'bg-brand-50 text-brand-800' => $facility->is_active,
                        'bg-slate-100 text-slate-400' => ! $facility->is_active,
                    ])>{{ $initials($facility->name) }}</span>
                    <x-page-title>{{ $facility->name }}</x-page-title>
                    @if (! $facility->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Inactive</span>
                    @elseif (! $facility->isOnboarded())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Setting up</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand-800"><span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>Live</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $facility->code }} · {{ $kinds[$facility->kind] ?? $facility->kind }} · {{ $facility->district }}
                    · Last activity {{ $lastActivity?->diffForHumans() ?? 'never' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card hover:shadow-card-hover">
                    <x-icon name="bx-user-check" class="text-lg text-brand-700" />
                    Administrators
                </a>
                <form method="POST" action="{{ route('platform.facilities.update', $facility) }}" @if ($facility->is_active) onsubmit="return confirm('Deactivate {{ addslashes($facility->name) }}? Its staff will be signed out and cannot sign in.')" @endif>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $facility->is_active ? 0 : 1 }}">
                    @if ($facility->is_active)
                        <button type="submit" class="inline-flex items-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-700">
                            <x-icon name="bx-power-off" class="text-lg" />
                            Deactivate
                        </button>
                    @else
                        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-brand hover:bg-brand-800">
                            <x-icon name="bx-power-off" class="text-lg" />
                            Activate
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </x-slot>

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Patients</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-group" class="text-xl" /></span>
            </div>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($patients['total']) }}</p>
            <p class="mt-auto pt-4 text-sm text-slate-500">
                <span class="font-semibold text-brand-700">+{{ number_format($patients['new30d']) }}</span> registered in the last 30 days
            </p>
        </div>

        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Visits today</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-pulse" class="text-xl" /></span>
            </div>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($visits['today']) }}</p>
            <p class="mt-auto pt-4 text-sm text-slate-500">{{ number_format($visits['last30d']) }} in the last 30 days · {{ number_format($visits['open']) }} open now</p>
        </div>

        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Collected · 30 days</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-wallet" class="text-xl" /></span>
            </div>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-sm font-semibold text-slate-400">{{ $facility->currency }}</span>
                <span class="text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($revenue['last30d']) }}</span>
            </p>
            <p class="mt-auto pt-4 text-sm text-slate-500">{{ \App\Support\Money::format($revenue['today'], $facility->currency) }} confirmed today</p>
        </div>

        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Bed occupancy</p>
                <span class="rounded-md bg-brand-50 px-2 py-1 text-[11px] font-semibold text-brand-800">{{ $beds['admitted'] }} admitted</span>
            </div>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold tracking-tight text-slate-900">{{ $occupancy }}%</span>
                <span class="text-sm text-slate-400">{{ $beds['occupied'] }} / {{ $beds['inService'] }} beds</span>
            </p>
            <div class="mt-auto pt-4">
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $occupancy >= 90 ? 'bg-red-600' : 'bg-brand-600' }}" style="width: {{ min($occupancy, 100) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $beds['inService'] }} of {{ $beds['planned'] }} planned beds set up</p>
            </div>
        </div>
    </div>

    {{-- Trend and visit mix --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Visits per week</h2>
                    <p class="text-sm text-slate-500">Last {{ $visits['trend']->count() }} weeks, by the Monday each week starts</p>
                </div>
                <p class="text-right">
                    <span class="block text-2xl font-extrabold tracking-tight text-slate-900">{{ number_format($trendTotal) }}</span>
                    <span class="block text-xs text-slate-400">visits in this period</span>
                </p>
            </div>

            <div class="mt-6 flex h-44 items-end gap-2 border-b border-slate-200" role="img" aria-label="Visits per week: {{ $visits['trend']->map(fn ($week) => $week['label'].' '.$week['count'])->join(', ') }}">
                @foreach ($visits['trend'] as $week)
                    @php($barHeight = $week['count'] > 0 ? max(6, (int) round($week['count'] / $trendPeak * 85)) : 2)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                        @if (($loop->last || $week['count'] === $visits['trend']->max('count')) && $week['count'] > 0)
                            <span class="mb-1 text-xs font-semibold tabular-nums text-slate-700">{{ $week['count'] }}</span>
                        @endif
                        <div class="w-full max-w-10 rounded-t {{ $week['count'] > 0 ? 'bg-brand-600 group-hover:bg-brand-700' : 'bg-slate-200' }}" style="height: {{ $barHeight }}%"></div>
                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2.5 py-1.5 text-xs text-white shadow-float group-hover:block">
                            <span class="font-semibold">{{ $week['count'] }} {{ str('visit')->plural($week['count']) }}</span>
                            <span class="text-slate-300">· week of {{ $week['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex gap-2">
                @foreach ($visits['trend'] as $week)
                    <span class="flex-1 text-center text-[11px] text-slate-400">{{ $loop->index % 2 === 0 || $loop->last ? $week['label'] : '' }}</span>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Visit mix</h2>
            <p class="text-sm text-slate-500">Visits opened in the last 30 days</p>

            <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">By payer</h3>
            <ul class="mt-2 space-y-3">
                @forelse ($visits['byPayer'] as $payer => $count)
                    <li class="text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-700">{{ \App\Enums\Payer::tryFrom($payer)?->label() ?? ucfirst($payer) }}</span>
                            <span class="tabular-nums text-slate-500"><span class="font-semibold text-slate-900">{{ number_format($count) }}</span> · {{ (int) round($count / $payerTotal * 100) }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ (int) round($count / $payerTotal * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No visits in the last 30 days.</li>
                @endforelse
            </ul>

            @if ($visits['byType']->isNotEmpty())
                <h3 class="mt-6 text-[11px] font-semibold uppercase tracking-wider text-slate-400">By type</h3>
                <ul class="mt-2 space-y-3">
                    @foreach ($visits['byType'] as $type => $count)
                        <li class="text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-700">{{ \App\Enums\VisitType::tryFrom($type)?->label() ?? ucfirst($type) }}</span>
                                <span class="tabular-nums text-slate-500"><span class="font-semibold text-slate-900">{{ number_format($count) }}</span> · {{ (int) round($count / $typeTotal * 100) }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-600" style="width: {{ (int) round($count / $typeTotal * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Services and stock --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Service activity</h2>
            <p class="text-sm text-slate-500">Orders and events in the last 30 days</p>
            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($clinical as $label => $count)
                    <div class="rounded-md bg-slate-50 px-4 py-3">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-extrabold tabular-nums tracking-tight {{ $count > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ number_format($count) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $stockIssues > 0 ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}">
                    <x-icon name="{{ $stockIssues > 0 ? 'bx-error' : 'bx-package' }}" class="text-xl" />
                </span>
                <div>
                    <h2 class="font-bold text-slate-900">Stock health</h2>
                    <p class="text-sm text-slate-500">{{ $stockIssues > 0 ? 'Worth a word with the administrator' : 'Nothing flagged' }}</p>
                </div>
            </div>
            <ul class="mt-5 space-y-2 text-sm">
                <li class="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2.5">
                    <span class="text-slate-600">Items at or below reorder level</span>
                    <span class="font-semibold tabular-nums {{ $stock['low'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $stock['low'] }}</span>
                </li>
                <li class="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2.5">
                    <span class="text-slate-600">Expired batches still on shelf</span>
                    <span class="font-semibold tabular-nums {{ $stock['expired'] > 0 ? 'text-red-700' : 'text-slate-900' }}">{{ $stock['expired'] }}</span>
                </li>
                <li class="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2.5">
                    <span class="text-slate-600">Batches expiring within 60 days</span>
                    <span class="font-semibold tabular-nums {{ $stock['expiringSoon'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $stock['expiringSoon'] }}</span>
                </li>
            </ul>
        </section>
    </div>

    {{-- People and setup --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Staff</h2>
            <p class="text-sm text-slate-500">{{ $staff['active'] }} active of {{ $staff['total'] }} accounts</p>
            <dl class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-md bg-slate-50 px-4 py-3">
                    <dt class="text-xs text-slate-500">Signed in · 7 days</dt>
                    <dd class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ $staff['signedIn7d'] }}</dd>
                </div>
                <div class="rounded-md bg-slate-50 px-4 py-3">
                    <dt class="text-xs text-slate-500">Never signed in</dt>
                    <dd class="mt-1 text-2xl font-extrabold tabular-nums {{ $staff['neverSignedIn'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $staff['neverSignedIn'] }}</dd>
                </div>
            </dl>
            <h3 class="mt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Active staff by role</h3>
            <ul class="mt-2 space-y-3">
                @forelse ($staff['byRole'] as $role => $count)
                    <li class="text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-700">{{ $role }}</span>
                            <span class="font-semibold tabular-nums text-slate-900">{{ $count }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ (int) round($count / $rolePeak * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No active staff.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg bg-white shadow-card">
            <div class="flex items-center justify-between gap-3 p-5">
                <div>
                    <h2 class="font-bold text-slate-900">Administrators</h2>
                    <p class="text-sm text-slate-500">Who runs this facility</p>
                </div>
                <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                    Manage
                    <x-icon name="bx-right-arrow-alt" class="text-lg" />
                </a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($admins as $admin)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span @class([
                            'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-xs font-bold',
                            'bg-brand-50 text-brand-800' => $admin->is_active,
                            'bg-slate-100 text-slate-400' => ! $admin->is_active,
                        ])>{{ $initials($admin->user->name) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-semibold text-slate-900">{{ $admin->user->name }}</span>
                                @if ($owner?->is($admin))
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand-800">Owner</span>
                                @endif
                                @if (! $admin->is_active)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">Inactive</span>
                                @endif
                            </span>
                            <span class="block truncate text-xs text-slate-500">{{ $admin->user->email }} · {{ $admin->user->hasVerifiedEmail() ? 'verified' : 'not verified' }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-slate-400">{{ $admin->last_login_at?->diffForHumans() ?? 'Never signed in' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-red-700">No administrator. Nobody can manage staff or prices here.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Profile and setup</h2>
            <dl class="mt-4 divide-y divide-slate-100 text-sm">
                @foreach ($profile as $label => $value)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="truncate text-right font-medium {{ filled($value) ? 'text-slate-900' : 'text-slate-300' }}">{{ filled($value) ? $value : 'Not set' }}</dd>
                    </div>
                @endforeach
                @foreach ($setup as $label => $count)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="font-medium tabular-nums text-slate-900">{{ number_format($count) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-app-layout>
