<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-shield-quarter">Insurance and corporate accounts</x-page-title></x-slot>
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('insurance.schemes.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Scheme</h2>
            <input name="name" required placeholder="Scheme name" class="rounded-md border-slate-300 text-sm">
            <select name="kind" class="rounded-md border-slate-300 text-sm">
                <option value="private">Private insurer</option>
                <option value="corporate">Corporate account</option>
            </select>
            <input name="cover_limit" type="number" min="0" placeholder="Cover limit, blank if none" class="rounded-md border-slate-300 text-sm">
            <label class="text-sm"><input type="checkbox" name="requires_preauthorisation" value="1"> Requires preauthorisation</label>
            <x-primary-button>Save scheme</x-primary-button>
            <ul class="mt-2 text-sm">
                @foreach ($schemes as $scheme)
                    <li>{{ $scheme->name }} · {{ $scheme->kind }} {{ $scheme->requires_preauthorisation ? '· preauth' : '' }}</li>
                @endforeach
            </ul>
        </form>
        <form method="POST" action="{{ route('insurance.preauthorisations.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Preauthorisation</h2>
            <select name="visit_id" class="rounded-md border-slate-300 text-sm">
                @foreach ($visits as $visit)
                    <option value="{{ $visit->id }}">{{ $visit->visit_number }} · {{ $visit->patient->fullName() }}</option>
                @endforeach
            </select>
            <input name="description" required placeholder="Service" class="rounded-md border-slate-300 text-sm">
            <input name="amount" type="number" min="1" required class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Request</x-primary-button>
        </form>
    </div>
    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Decisions</h2>
        @foreach ($preauthorisations as $preauthorisation)
            <form method="POST" action="{{ route('insurance.preauthorisations.decide', $preauthorisation) }}" class="mt-3 grid gap-2 border-t border-slate-100 pt-3 text-sm md:grid-cols-4">
                @csrf
                <span>{{ $preauthorisation->visit->patient->fullName() }} · {{ $preauthorisation->description }} · {{ $preauthorisation->status }}</span>
                <input name="reference" placeholder="Reference" class="rounded-md border-slate-300 text-sm">
                <select name="decision" class="rounded-md border-slate-300 text-sm">
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                </select>
                <x-primary-button>Save</x-primary-button>
            </form>
        @endforeach
    </section>
    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Claims</h2>
        <form method="POST" action="{{ route('insurance.claims.store') }}" class="mt-3 grid gap-2 md:grid-cols-4">
            @csrf
            <select name="insurance_scheme_id" class="rounded-md border-slate-300 text-sm">
                @foreach ($schemes as $scheme)
                    <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                @endforeach
            </select>
            <input type="date" name="period_start" required class="rounded-md border-slate-300 text-sm">
            <input type="date" name="period_end" required class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Compile claim</x-primary-button>
        </form>
        @foreach ($claims as $claim)
            <div class="mt-4 border-t border-slate-100 pt-3 text-sm">
                <p>{{ $claim->number }} · {{ $claim->scheme->name }} · {{ \App\Support\Money::format($claim->total_amount) }} · {{ $claim->status }}</p>
                @if ($claim->status === 'draft')
                    <form method="POST" action="{{ route('insurance.claims.submit', $claim) }}" class="mt-2">@csrf<x-primary-button>Submit</x-primary-button></form>
                @endif
                @if (in_array($claim->status, ['submitted', 'remitted'], true))
                    <form method="POST" action="{{ route('insurance.claims.remit', $claim) }}" class="mt-2 grid gap-2 md:grid-cols-3">
                        @csrf
                        <input name="amount" type="number" min="1" required class="rounded-md border-slate-300 text-sm">
                        <input name="reference" required placeholder="Remittance reference" class="rounded-md border-slate-300 text-sm">
                        <x-primary-button>Record remittance</x-primary-button>
                    </form>
                @endif
            </div>
        @endforeach
    </section>
</x-app-layout>
