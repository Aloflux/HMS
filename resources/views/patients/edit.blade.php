<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Edit {{ $patient->fullName() }}</x-page-title>
            <p class="mt-1 text-sm text-slate-500">{{ $patient->patient_number }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('patients.update', $patient) }}" enctype="multipart/form-data" class="grid max-w-3xl gap-4 rounded-lg bg-white p-6 shadow-card sm:grid-cols-2">
        @csrf
        @method('PATCH')
        <div>
            <x-input-label for="surname" value="Surname" />
            <x-text-input id="surname" name="surname" class="mt-1 block w-full" :value="old('surname', $patient->surname)" required />
            <x-input-error :messages="$errors->get('surname')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="given_name" value="Given name" />
            <x-text-input id="given_name" name="given_name" class="mt-1 block w-full" :value="old('given_name', $patient->given_name)" required />
            <x-input-error :messages="$errors->get('given_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="other_name" value="Other name" />
            <x-text-input id="other_name" name="other_name" class="mt-1 block w-full" :value="old('other_name', $patient->other_name)" />
        </div>
        <div>
            <x-input-label for="sex" value="Sex" />
            <select id="sex" name="sex" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 shadow-none focus:outline-none" required>
                <option value="female" @selected(old('sex', $patient->sex->value) === 'female')>Female</option>
                <option value="male" @selected(old('sex', $patient->sex->value) === 'male')>Male</option>
            </select>
        </div>
        <div>
            <x-input-label for="date_of_birth" value="Date of birth" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $patient->date_of_birth->format('Y-m-d'))" required />
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $patient->phone)" required />
        </div>
        <div>
            <x-input-label for="national_id" value="National ID" />
            <x-text-input id="national_id" name="national_id" class="mt-1 block w-full" :value="old('national_id', $patient->national_id)" />
            <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="district" value="District" />
            <x-text-input id="district" name="district" class="mt-1 block w-full" :value="old('district', $patient->district)" required />
        </div>
        <div>
            <x-input-label for="village" value="Village" />
            <x-text-input id="village" name="village" class="mt-1 block w-full" :value="old('village', $patient->village)" />
        </div>
        <div>
            <x-input-label for="blood_group" value="Blood group" />
            <x-text-input id="blood_group" name="blood_group" class="mt-1 block w-full" :value="old('blood_group', $patient->blood_group)" />
        </div>
        <div>
            <x-input-label for="next_of_kin_name" value="Next of kin" />
            <x-text-input id="next_of_kin_name" name="next_of_kin_name" class="mt-1 block w-full" :value="old('next_of_kin_name', $patient->next_of_kin_name)" required />
        </div>
        <div>
            <x-input-label for="next_of_kin_relationship" value="Relationship" />
            <x-text-input id="next_of_kin_relationship" name="next_of_kin_relationship" class="mt-1 block w-full" :value="old('next_of_kin_relationship', $patient->next_of_kin_relationship)" required />
        </div>
        <div>
            <x-input-label for="next_of_kin_phone" value="Next of kin phone" />
            <x-text-input id="next_of_kin_phone" name="next_of_kin_phone" class="mt-1 block w-full" :value="old('next_of_kin_phone', $patient->next_of_kin_phone)" required />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="allergies" value="Known allergies" />
            <textarea id="allergies" name="allergies" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 shadow-none focus:outline-none">{{ old('allergies', $patient->allergies) }}</textarea>
        </div>
        <div>
            <x-input-label for="photo" value="Photo" />
            <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm">
        </div>
        <label class="flex items-start gap-2 text-sm sm:col-span-2">
            <input type="checkbox" name="confirm_duplicate" value="1" class="mt-1 rounded border-slate-300 focus:ring-brand-500 text-brand-700" @checked(old('confirm_duplicate'))>
            <span>Save anyway if the phone, name and date of birth already match another record.</span>
        </label>
        <x-input-error :messages="$errors->get('confirm_duplicate')" class="sm:col-span-2" />
        <div class="sm:col-span-2"><x-primary-button>Save patient</x-primary-button></div>
    </form>
</x-app-layout>
