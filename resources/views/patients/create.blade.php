<x-app-layout>
    <x-slot name="header">
        <x-page-title icon="bx-user-plus">Register a patient</x-page-title>
    </x-slot>

    <form method="POST" action="{{ route('patients.store') }}" enctype="multipart/form-data" class="grid max-w-3xl gap-4 rounded-lg bg-white p-6 shadow-sm sm:grid-cols-2">
        @csrf
        <div>
            <x-input-label for="surname" value="Surname" />
            <x-text-input id="surname" name="surname" class="mt-1 block w-full" :value="old('surname')" required />
        </div>
        <div>
            <x-input-label for="given_name" value="Given name" />
            <x-text-input id="given_name" name="given_name" class="mt-1 block w-full" :value="old('given_name')" required />
        </div>
        <div>
            <x-input-label for="other_name" value="Other name" />
            <x-text-input id="other_name" name="other_name" class="mt-1 block w-full" :value="old('other_name')" />
        </div>
        <div>
            <x-input-label for="sex" value="Sex" />
            <select id="sex" name="sex" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-600 focus:ring-brand-600" required>
                <option value="female" @selected(old('sex') === 'female')>Female</option>
                <option value="male" @selected(old('sex') === 'male')>Male</option>
            </select>
        </div>
        <div>
            <x-input-label for="date_of_birth" value="Date of birth" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" required />
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" required />
        </div>
        <div>
            <x-input-label for="national_id" value="National ID" />
            <x-text-input id="national_id" name="national_id" class="mt-1 block w-full" :value="old('national_id')" />
        </div>
        <div>
            <x-input-label for="district" value="District" />
            <x-text-input id="district" name="district" class="mt-1 block w-full" :value="old('district')" required />
        </div>
        <div>
            <x-input-label for="village" value="Village" />
            <x-text-input id="village" name="village" class="mt-1 block w-full" :value="old('village')" />
        </div>
        <div>
            <x-input-label for="blood_group" value="Blood group" />
            <x-text-input id="blood_group" name="blood_group" class="mt-1 block w-full" :value="old('blood_group')" />
        </div>
        <div>
            <x-input-label for="next_of_kin_name" value="Next of kin" />
            <x-text-input id="next_of_kin_name" name="next_of_kin_name" class="mt-1 block w-full" :value="old('next_of_kin_name')" required />
        </div>
        <div>
            <x-input-label for="next_of_kin_relationship" value="Relationship" />
            <x-text-input id="next_of_kin_relationship" name="next_of_kin_relationship" class="mt-1 block w-full" :value="old('next_of_kin_relationship')" required />
        </div>
        <div>
            <x-input-label for="next_of_kin_phone" value="Next of kin phone" />
            <x-text-input id="next_of_kin_phone" name="next_of_kin_phone" class="mt-1 block w-full" :value="old('next_of_kin_phone')" required />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="allergies" value="Known allergies" />
            <textarea id="allergies" name="allergies" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-600 focus:ring-brand-600">{{ old('allergies') }}</textarea>
        </div>
        <div>
            <x-input-label for="photo" value="Photo" />
            <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm">
        </div>
        <label class="flex items-start gap-2 text-sm sm:col-span-2">
            <input type="checkbox" name="consent" value="1" class="mt-1 rounded border-slate-300 text-brand-700" required>
            <span>The patient (or guardian) consents to this facility keeping their health record under the Data Protection and Privacy Act, 2019.</span>
        </label>
        <label class="flex items-start gap-2 text-sm sm:col-span-2">
            <input type="checkbox" name="confirm_duplicate" value="1" class="mt-1 rounded border-slate-300 text-brand-700">
            <span>Register anyway if the phone, name and date of birth already match a record.</span>
        </label>
        <div class="sm:col-span-2">
            <x-primary-button>Save patient</x-primary-button>
        </div>
    </form>
</x-app-layout>
