@props(['code' => '', 'facility' => null, 'autofocus' => false])

{{--
    Facility code input that shows the facility's name and district once a valid code is typed.
    Each facility has its own password, so people check here which facility they are signing in to.
--}}
<div x-data="facilityLookup({ url: @js(route('login.facility')), code: @js(old('facility_code', $code)), facility: @js($facility) })">
    <x-input-label for="facility_code" value="Facility code" />
    <x-text-input id="facility_code" class="mt-1 block w-full uppercase" name="facility_code" x-model="code" @input="lookup()" maxlength="12" autocomplete="organization" :autofocus="$autofocus" />

    <p class="mt-2 flex items-start gap-2 rounded-md bg-brand-50 px-3 py-2 text-sm text-brand-900" x-show="facility" x-cloak aria-live="polite">
        <x-icon name="bx-buildings" class="mt-0.5 text-base text-brand-700" />
        <span>
            <span class="block font-semibold" x-text="facility?.name"></span>
            <span class="block text-xs text-brand-800" x-show="facility?.district" x-text="facility?.district"></span>
        </span>
    </p>
    <p class="mt-1 text-xs text-red-700" x-show="checked && ! facility && code.trim() !== ''" x-cloak>No active facility has this code.</p>
    <p class="mt-1 text-xs text-slate-500" x-show="code.trim() === ''">Staff: each facility you work at has its own code and password. Facility administrators and platform operators can leave this blank.</p>

    <x-input-error :messages="$errors->get('facility_code')" class="mt-2" />
</div>
