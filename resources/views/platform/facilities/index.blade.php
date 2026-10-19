<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-buildings">Facilities</x-page-title></x-slot>
    <form method="POST" action="{{ route('platform.facilities.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-3">
        @csrf
        <input name="name" required placeholder="Facility name" class="rounded-md border-slate-300 text-sm">
        <input name="code" required placeholder="Code" class="rounded-md border-slate-300 text-sm">
        <select name="kind" class="rounded-md border-slate-300 text-sm">
            <option value="hospital">Hospital</option>
            <option value="clinic">Clinic</option>
            <option value="faith_based">Faith-based</option>
        </select>
        <input name="district" required placeholder="District" class="rounded-md border-slate-300 text-sm">
        <input name="bed_capacity" type="number" min="1" required placeholder="Beds" class="rounded-md border-slate-300 text-sm">
        <input name="admin_name" required placeholder="Administrator name" class="rounded-md border-slate-300 text-sm">
        <input name="admin_email" type="email" required placeholder="Administrator email" class="rounded-md border-slate-300 text-sm">
        <input name="admin_password" type="password" required placeholder="Temporary password" class="rounded-md border-slate-300 text-sm">
        <div class="md:col-span-3"><x-primary-button>Provision facility</x-primary-button></div>
    </form>
    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            @foreach ($facilities as $facility)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3">{{ $facility->name }}<br><span class="text-slate-500">{{ $facility->code }} · {{ $facility->district }} · {{ $facility->patients_count }} patients</span></td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('platform.facilities.update', $facility) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="is_active" class="rounded-md border-slate-300 text-sm">
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
