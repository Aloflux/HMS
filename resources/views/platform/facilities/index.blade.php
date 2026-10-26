<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-buildings">Facilities</x-page-title></x-slot>
    <form method="POST" action="{{ route('platform.facilities.store') }}" class="rounded-2xl bg-white p-5 shadow-card">
        @csrf
        <h2 class="font-bold text-slate-900">Provision a facility</h2>
        <p class="mt-1 text-sm text-slate-500">Creates the departments, stores, wards, starter prices, and a first administrator.</p>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div><x-input-label for="facility-name" value="Facility name" /><x-text-input id="facility-name" name="name" class="mt-1 block w-full" :value="old('name')" required /></div>
            <div><x-input-label for="facility-code" value="Short code" /><x-text-input id="facility-code" name="code" class="mt-1 block w-full" :value="old('code')" placeholder="Used in patient numbers" required /></div>
            <div>
                <x-input-label for="facility-kind" value="Kind" />
                <select id="facility-kind" name="kind" class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                    <option value="hospital">Hospital</option>
                    <option value="clinic" @selected(old('kind') === 'clinic')>Clinic</option>
                    <option value="faith_based" @selected(old('kind') === 'faith_based')>Faith-based</option>
                </select>
            </div>
            <div><x-input-label for="facility-district" value="District" /><x-text-input id="facility-district" name="district" class="mt-1 block w-full" :value="old('district')" required /></div>
            <div><x-input-label for="facility-beds" value="Beds" /><x-text-input id="facility-beds" name="bed_capacity" type="number" min="1" max="500" class="mt-1 block w-full" :value="old('bed_capacity')" required /></div>
        </div>
        <h3 class="mt-5 text-sm font-semibold">First administrator</h3>
        <div class="mt-2 grid gap-3 md:grid-cols-3">
            <div><x-input-label for="admin-name" value="Name" /><x-text-input id="admin-name" name="admin_name" class="mt-1 block w-full" :value="old('admin_name')" required /></div>
            <div><x-input-label for="admin-email" value="Email" /><x-text-input id="admin-email" name="admin_email" type="email" class="mt-1 block w-full" :value="old('admin_email')" required /></div>
            <div><x-input-label for="admin-password" value="Temporary password" /><x-text-input id="admin-password" name="admin_password" type="password" autocomplete="new-password" class="mt-1 block w-full" required /></div>
        </div>
        <div class="mt-4"><x-primary-button>Provision facility</x-primary-button></div>
    </form>
    <div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="min-w-full text-sm">
            @foreach ($facilities as $facility)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3">{{ $facility->name }}<br><span class="text-slate-500">{{ $facility->code }} · {{ $facility->district }} · {{ $facility->patients_count }} patients</span></td>
                    <td class="px-4 py-3">
                        <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="font-semibold text-brand-700">{{ $facility->admins_count }} {{ Str::plural('administrator', $facility->admins_count) }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('platform.facilities.update', $facility) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="is_active" class="rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                <option value="1" @selected($facility->is_active)>Active</option>
                                <option value="0" @selected(! $facility->is_active)>Inactive</option>
                            </select>
                            <button class="text-brand-700">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</x-app-layout>
