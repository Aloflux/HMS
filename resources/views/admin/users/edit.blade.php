<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-700">Users</a>
            <x-page-title>{{ $membership->displayName() }}</x-page-title>
            <p class="mt-1 text-sm text-slate-500">
                {{ $membership->user->email }} ·
                @if ($membership->isAccepted())
                    Last signed in here {{ $membership->last_login_at?->diffForHumans() ?? 'never' }}.
                @else
                    Invited {{ $membership->invited_at?->diffForHumans() }}{{ $membership->invitationHasExpired() ? '. The link has expired' : '' }}.
                @endif
            </p>
        </div>
    </x-slot>

    <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-5 shadow-card">
        @if ($membership->isAccepted())
            <p class="text-sm text-slate-600">Their name, email and password belong to them and are changed from their own profile.</p>
            <form method="POST" action="{{ route('admin.users.password-link', $membership) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                    <x-icon name="bx-key" class="text-lg" />
                    Send password reset link
                </button>
            </form>
        @else
            <p class="text-sm text-slate-600">They have not accepted yet. You can set their access now; it applies once they join.</p>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.users.invitation.resend', $membership) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                        <x-icon name="bx-envelope" class="text-lg" />
                        Send invitation again
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.users.invitation.cancel', $membership) }}" onsubmit="return confirm('Withdraw this invitation?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-700">
                        <x-icon name="bx-x" class="text-lg" />
                        Withdraw invitation
                    </button>
                </form>
            </div>
        @endif
    </section>

    <form
        method="POST"
        action="{{ route('admin.users.update', $membership) }}"
        class="rounded-lg bg-white p-5 shadow-card"
        x-data="{
            account: @js(old('account', $membership->role === \App\Enums\Role::Administrator ? 'administrator' : 'staff')),
            areas: @js((object) array_fill_keys(old('areas', $membership->selectedAreaKeys()), true)),
            departmentAreas: @js($departmentAreas),
            applyDepartment(id) {
                (this.departmentAreas[id] || []).forEach((key) => { this.areas[key] = true; });
            },
        }"
    >
        @csrf
        @method('PATCH')

        @include('admin.users.partials.access', ['areas' => $areas, 'departments' => $departments, 'selectedDepartment' => $membership->department_id])

        <fieldset class="mt-6">
            <legend class="text-sm font-medium text-slate-700">Status at this facility</legend>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach (['1' => 'Active, can work here', '0' => 'Inactive, cannot work here'] as $value => $label)
                    <label class="inline-flex items-center gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="radio" name="is_active" value="{{ $value }}" class="text-brand-700" @checked((string) old('is_active', (int) $membership->is_active) === (string) $value)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-6 flex items-center gap-4">
            <x-primary-button>Save changes</x-primary-button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600">Cancel</a>
        </div>
    </form>
</x-app-layout>
