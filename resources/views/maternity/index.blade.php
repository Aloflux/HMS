<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-heart">Maternity</x-page-title></x-slot>
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('maternity.antenatal') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Antenatal visit</h2>
            <select name="patient_id" class="rounded-md border-slate-300 text-sm">@foreach ($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->fullName() }}</option>@endforeach</select>
            <input name="gestation_weeks" type="number" min="1" required placeholder="Gestation weeks" class="rounded-md border-slate-300 text-sm">
            <input name="fundal_height_cm" type="number" placeholder="Fundal height cm" class="rounded-md border-slate-300 text-sm">
            <input name="fetal_heart" placeholder="Fetal heart" class="rounded-md border-slate-300 text-sm">
            <input name="blood_pressure" placeholder="Blood pressure" class="rounded-md border-slate-300 text-sm">
            <textarea name="notes" class="rounded-md border-slate-300 text-sm"></textarea>
            <x-primary-button>Save antenatal visit</x-primary-button>
        </form>
        <form method="POST" action="{{ route('maternity.deliveries') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Delivery register</h2>
            <select name="patient_id" class="rounded-md border-slate-300 text-sm">@foreach ($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->fullName() }}</option>@endforeach</select>
            <input type="datetime-local" name="delivered_at" required class="rounded-md border-slate-300 text-sm">
            <select name="mode" class="rounded-md border-slate-300 text-sm"><option value="svd">SVD</option><option value="caesarean">Caesarean</option><option value="assisted">Assisted</option></select>
            <select name="outcome" class="rounded-md border-slate-300 text-sm"><option value="live">Live birth</option><option value="stillbirth">Stillbirth</option></select>
            <input name="birth_weight_kg" placeholder="Birth weight kg" class="rounded-md border-slate-300 text-sm">
            <input name="apgar" type="number" min="0" max="10" placeholder="Apgar" class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Save delivery</x-primary-button>
        </form>
    </div>
    <section class="mt-6 rounded-lg bg-white p-5 text-sm shadow-sm">
        <h2 class="font-semibold">Recent antenatal visits</h2>
        @foreach ($antenatal as $row)
            <p class="mt-2">{{ $row->patient->fullName() }} · {{ $row->gestation_weeks }} weeks · {{ $row->seen_at->format('d M Y') }}</p>
        @endforeach
        <h2 class="mt-4 font-semibold">Deliveries</h2>
        @foreach ($deliveries as $row)
            <p class="mt-2">{{ $row->patient->fullName() }} · {{ $row->mode }} · {{ $row->outcome }}</p>
        @endforeach
    </section>
</x-app-layout>
