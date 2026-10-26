<fieldset>
    <legend class="text-sm font-medium text-slate-700">Access</legend>
    <div class="mt-2 flex flex-wrap gap-2">
        <label class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm" :class="account === 'administrator' ? 'bg-brand-50 font-semibold text-brand-800' : 'bg-slate-50 text-slate-700'">
            <input type="radio" name="account" value="administrator" class="text-brand-700" x-model="account">
            Administrator, full access
        </label>
        <label class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm" :class="account === 'staff' ? 'bg-brand-50 font-semibold text-brand-800' : 'bg-slate-50 text-slate-700'">
            <input type="radio" name="account" value="staff" class="text-brand-700" x-model="account">
            Chosen areas
        </label>
    </div>
    <x-input-error :messages="$errors->get('account')" class="mt-1" />
</fieldset>

<div class="mt-4">
    <x-input-label for="department_id" value="Department" />
    <select id="department_id" name="department_id" class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm shadow-none focus:outline-none sm:max-w-sm" @change="applyDepartment($event.target.value)">
        <option value="">No department</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected((string) old('department_id', $selectedDepartment ?? null) === (string) $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-sm text-slate-500" x-show="account === 'staff'">Choosing a department ticks the areas that department uses. You can change the ticks.</p>
</div>

<div class="mt-4" x-show="account === 'staff'" x-cloak>
    <div class="grid gap-2 sm:grid-cols-2">
        @foreach ($areas as $area)
            <label class="flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                <input type="checkbox" name="areas[]" value="{{ $area['key'] }}" class="mt-1 rounded border-slate-300 focus:ring-brand-500 text-brand-700" x-model="areas['{{ $area['key'] }}']">
                <span>
                    <span class="block font-medium text-slate-900">{{ $area['label'] }}</span>
                    <span class="block text-slate-500">{{ $area['detail'] }}</span>
                </span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('areas')" class="mt-1" />
</div>
