<x-app-layout>
    <x-slot name="header"><x-page-title>Insurance and corporate accounts</x-page-title></x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Schemes</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($schemes as $scheme)
                    <li class="flex flex-wrap justify-between gap-2 py-2">
                        <span class="font-medium">{{ $scheme->name }}</span>
                        <span class="text-slate-500">
                            {{ $scheme->kind === 'corporate' ? 'Corporate' : 'Private insurer' }}
                            · {{ $scheme->cover_limit !== null ? 'limit '.\App\Support\Money::format($scheme->cover_limit) : 'no limit' }}
                            {{ $scheme->requires_preauthorisation ? '· needs preauthorisation' : '' }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-slate-500">No schemes yet.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('insurance.schemes.store') }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="scheme-name" value="Scheme name" />
                    <x-text-input id="scheme-name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                </div>
                <div>
                    <x-input-label for="scheme-kind" value="Kind" />
                    <select id="scheme-kind" name="kind" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        <option value="private">Private insurer</option>
                        <option value="corporate" @selected(old('kind') === 'corporate')>Corporate account</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="scheme-limit" value="Cover limit per visit (UGX)" />
                    <x-text-input id="scheme-limit" name="cover_limit" type="number" min="0" class="mt-1 block w-full" :value="old('cover_limit')" placeholder="Leave blank for no limit" />
                </div>
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" name="requires_preauthorisation" value="1" class="rounded border-slate-300 focus:ring-brand-500 text-brand-700" @checked(old('requires_preauthorisation'))>
                    Charges need an approved preauthorisation first
                </label>
                <div class="sm:col-span-2"><x-primary-button>Add scheme</x-primary-button></div>
            </form>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Request a preauthorisation</h2>
            @if ($visits->isEmpty())
                <p class="mt-3 text-sm text-slate-500">No insured visits yet. Preauthorisations are for visits paid by a scheme.</p>
            @else
                <form method="POST" action="{{ route('insurance.preauthorisations.store') }}" class="mt-3 grid gap-3">
                    @csrf
                    <div>
                        <x-input-label for="preauth-visit" value="Visit" />
                        <x-search-select id="preauth-visit" name="visit_id" :options="$visits->map(fn ($visit) => ['value' => $visit->id, 'label' => $visit->patient->fullName(), 'hint' => $visit->visit_number])" placeholder="Search by patient or visit number" required />
                    </div>
                    <div>
                        <x-input-label for="preauth-service" value="Service" />
                        <x-text-input id="preauth-service" name="description" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="preauth-amount" value="Amount (UGX)" />
                        <x-text-input id="preauth-amount" name="amount" type="number" min="1" class="mt-1 block w-full" required />
                    </div>
                    <div><x-primary-button>Request</x-primary-button></div>
                </form>
            @endif
        </section>
    </div>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">Preauthorisations</h2>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @forelse ($preauthorisations as $preauthorisation)
                <li class="grid gap-3 py-3 md:grid-cols-2 md:items-center">
                    <span>
                        <span class="block font-medium">{{ $preauthorisation->visit->patient->fullName() }} · {{ $preauthorisation->description }}</span>
                        <span class="block text-xs text-slate-500">{{ $preauthorisation->scheme?->name }} · {{ \App\Support\Money::format((int) $preauthorisation->amount) }}{{ $preauthorisation->reference ? ' · ref '.$preauthorisation->reference : '' }}</span>
                    </span>
                    @if ($preauthorisation->status === 'pending')
                        <form method="POST" action="{{ route('insurance.preauthorisations.decide', $preauthorisation) }}" class="flex flex-wrap items-center gap-2 md:justify-end">
                            @csrf
                            <x-text-input name="reference" class="block w-40" placeholder="Insurer reference" aria-label="Insurer reference" />
                            <button name="decision" value="rejected" class="text-sm text-red-700">Reject</button>
                            <x-primary-button name="decision" value="approved">Approve</x-primary-button>
                        </form>
                    @else
                        <span class="md:text-end"><x-status :value="$preauthorisation->status" /></span>
                    @endif
                </li>
            @empty
                <li class="py-2 text-slate-500">No preauthorisations yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">Claims</h2>
        @if ($schemes->isNotEmpty())
            <form method="POST" action="{{ route('insurance.claims.store') }}" class="mt-3 grid gap-3 md:grid-cols-4">
                @csrf
                <div>
                    <x-input-label for="claim-scheme" value="Scheme" />
                    <select id="claim-scheme" name="insurance_scheme_id" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        @foreach ($schemes as $scheme)
                            <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="claim-start" value="From" />
                    <x-text-input id="claim-start" type="date" name="period_start" class="mt-1 block w-full" :value="now()->startOfMonth()->toDateString()" required />
                </div>
                <div>
                    <x-input-label for="claim-end" value="To" />
                    <x-text-input id="claim-end" type="date" name="period_end" class="mt-1 block w-full" :value="now()->toDateString()" required />
                </div>
                <div class="flex items-end"><x-primary-button>Compile claim</x-primary-button></div>
            </form>
        @endif
        <ul class="mt-4 divide-y divide-slate-100 text-sm">
            @forelse ($claims as $claim)
                <li class="py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="font-medium">{{ $claim->number }} · {{ $claim->scheme->name }} · {{ \App\Support\Money::format($claim->total_amount) }}</span>
                        <span class="flex items-center gap-3">
                            <x-status :value="$claim->status" />
                            @if ($claim->status === 'draft')
                                <form method="POST" action="{{ route('insurance.claims.submit', $claim) }}">@csrf<x-primary-button>Submit to insurer</x-primary-button></form>
                            @endif
                        </span>
                    </div>
                    @if (in_array($claim->status, ['submitted', 'remitted'], true))
                        <form method="POST" action="{{ route('insurance.claims.remit', $claim) }}" class="mt-3 grid gap-3 md:grid-cols-3">
                            @csrf
                            <div>
                                <x-input-label :for="'remit-amount-'.$claim->id" value="Amount received (UGX)" />
                                <x-text-input :id="'remit-amount-'.$claim->id" name="amount" type="number" min="1" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label :for="'remit-ref-'.$claim->id" value="Remittance reference" />
                                <x-text-input :id="'remit-ref-'.$claim->id" name="reference" class="mt-1 block w-full" required />
                            </div>
                            <div class="flex items-end"><x-secondary-button type="submit">Record remittance</x-secondary-button></div>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-2 text-slate-500">No claims yet.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
