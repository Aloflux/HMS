<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\Ward;
use App\Notifications\FacilityReady;
use App\Services\WardManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * @var list<string>
     */
    public const STEPS = ['facility', 'wards', 'finish'];

    public function show(Request $request, string $step = 'facility'): View|RedirectResponse
    {
        $facility = $this->facilityBeingSetUp($request);

        if ($facility->isOnboarded()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.show', [
            'facility' => $facility,
            'step' => $step,
            'steps' => self::STEPS,
            'kinds' => RegisteredUserController::FACILITY_KINDS,
            'wards' => Ward::query()->withCount('beds')->orderBy('id')->get(),
        ]);
    }

    public function saveFacility(Request $request): RedirectResponse
    {
        $facility = $this->facilityBeingSetUp($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'kind' => ['required', Rule::in(array_keys(RegisteredUserController::FACILITY_KINDS))],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'moh_code' => ['nullable', 'string', 'max:30'],
        ]);

        $facility->update($data);

        return redirect()->route('onboarding.show', 'wards');
    }

    public function saveWards(Request $request, WardManager $wardManager): RedirectResponse
    {
        $facility = $this->facilityBeingSetUp($request);
        $data = $request->validate([
            'wards' => ['array'],
            'wards.*.name' => ['required', 'string', 'max:150'],
            'wards.*.beds' => ['required', 'integer', 'min:0', 'max:200'],
            'new_wards' => ['array', 'max:20'],
            'new_wards.*.name' => ['nullable', 'string', 'max:150'],
            'new_wards.*.beds' => ['nullable', 'integer', 'min:0', 'max:200'],
        ], [
            'wards.*.name.required' => 'Every ward needs a name.',
        ]);

        DB::transaction(function () use ($data, $facility, $wardManager): void {
            $wards = Ward::query()->whereIn('id', array_keys($data['wards'] ?? []))->withCount('beds')->get();

            foreach ($wards as $ward) {
                $wanted = (int) $data['wards'][$ward->id]['beds'];
                $ward->update(['name' => $data['wards'][$ward->id]['name']]);

                if ($wanted > $ward->beds_count) {
                    $wardManager->addBeds($ward, $wanted - $ward->beds_count);
                } elseif ($wanted < $ward->beds_count) {
                    $wardManager->removeUnusedBeds($ward, $ward->beds_count - $wanted);
                }
            }

            foreach ($data['new_wards'] ?? [] as $row) {
                if (blank($row['name'] ?? null)) {
                    continue;
                }

                $wardManager->create(
                    $facility->id,
                    $row['name'],
                    $this->uniqueWardCode($row['name']),
                    null,
                    (int) ($row['beds'] ?? 0),
                    withStore: true,
                );
            }
        });

        return redirect()->route('onboarding.show', 'finish');
    }

    public function finish(Request $request): RedirectResponse
    {
        $facility = $this->facilityBeingSetUp($request);

        $facility->update([
            'onboarded_at' => now(),
            'bed_capacity' => Bed::query()->where('status', '!=', 'closed')->count(),
        ]);

        $request->user()->notify(new FacilityReady($facility));

        return redirect()->route('dashboard')->with('status', $facility->name.' is live. We have emailed you a short list of next steps.');
    }

    /**
     * Only the owner (an administrator) of a facility that is still being set up may use the wizard.
     */
    private function facilityBeingSetUp(Request $request): Facility
    {
        $user = $request->user();

        abort_unless($user->facility !== null && $user->role === Role::Administrator, 403);

        return $user->facility;
    }

    /**
     * Short internal code for a new ward, e.g. "Children's ward" becomes "CW", or "CW2" if taken.
     */
    private function uniqueWardCode(string $name): string
    {
        $base = Str::of($name)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z ]/', '')
            ->explode(' ')
            ->filter()
            ->map(fn (string $word): string => $word[0])
            ->take(4)
            ->implode('') ?: 'W';

        $code = $base;
        $suffix = 2;

        while (Ward::query()->where('code', $code)->exists()) {
            $code = $base.$suffix++;
        }

        return $code;
    }
}
