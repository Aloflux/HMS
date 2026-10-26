<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Weeks of sign-up history shown on the trend chart.
     */
    private const TREND_WEEKS = 8;

    /**
     * Platform-wide overview. Only aggregate counts cross tenants; no patient record is ever loaded.
     */
    public function __invoke(): View
    {
        $facilities = Facility::query()
            ->withCount([
                'users as active_admins_count' => fn ($query) => $query->where('role', Role::Administrator)->where('is_active', true),
                'users as staff_count',
            ])
            ->orderBy('name')
            ->get();

        $patients = Patient::query()->withoutGlobalScope('facility')->selectRaw('facility_id, count(*) as total')->groupBy('facility_id')->pluck('total', 'facility_id');
        $recentVisits = Visit::query()->withoutGlobalScope('facility')->where('opened_at', '>=', now()->subDays(30))->selectRaw('facility_id, count(*) as total')->groupBy('facility_id')->pluck('total', 'facility_id');
        $bedsInService = Bed::query()->withoutGlobalScope('facility')->where('status', '!=', 'closed')->selectRaw('facility_id, count(*) as total')->groupBy('facility_id')->pluck('total', 'facility_id');
        $bedsOccupied = Bed::query()->withoutGlobalScope('facility')->where('status', 'occupied')->selectRaw('facility_id, count(*) as total')->groupBy('facility_id')->pluck('total', 'facility_id');

        $facilities->each(function (Facility $facility) use ($patients, $recentVisits, $bedsInService, $bedsOccupied): void {
            $facility->setAttribute('patients_total', (int) ($patients[$facility->id] ?? 0));
            $facility->setAttribute('visits_30d', (int) ($recentVisits[$facility->id] ?? 0));
            $facility->setAttribute('beds_in_service', (int) ($bedsInService[$facility->id] ?? 0));
            $facility->setAttribute('beds_occupied', (int) ($bedsOccupied[$facility->id] ?? 0));
        });

        $owners = User::query()
            ->whereIn('facility_id', $facilities->whereNull('onboarded_at')->pluck('id'))
            ->where('role', Role::Administrator)
            ->orderBy('id')
            ->get()
            ->unique('facility_id')
            ->keyBy('facility_id');

        $weekStart = now()->startOfWeek()->subWeeks(self::TREND_WEEKS - 1);
        $signUps = collect(range(0, self::TREND_WEEKS - 1))->map(function (int $offset) use ($facilities, $weekStart): array {
            $from = $weekStart->copy()->addWeeks($offset);
            $to = $from->copy()->endOfWeek();

            return [
                'label' => $from->format('d M'),
                'count' => $facilities->filter(fn (Facility $facility): bool => $facility->created_at instanceof Carbon && $facility->created_at->between($from, $to))->count(),
            ];
        });

        return view('platform.dashboard', [
            'stats' => [
                'facilities' => $facilities->count(),
                'active' => $facilities->where('is_active', true)->count(),
                'settingUp' => $facilities->whereNull('onboarded_at')->count(),
                'newThisMonth' => $facilities->filter(fn (Facility $facility): bool => $facility->created_at?->greaterThanOrEqualTo(now()->startOfMonth()) ?? false)->count(),
                'users' => User::query()->whereNotNull('facility_id')->where('is_active', true)->count(),
                'patients' => (int) $patients->sum(),
                'visitsToday' => Visit::query()->withoutGlobalScope('facility')->where('opened_at', '>=', today())->count(),
                'visits30d' => (int) $recentVisits->sum(),
                'bedsInService' => (int) $bedsInService->sum(),
                'bedsOccupied' => (int) $bedsOccupied->sum(),
            ],
            'signUps' => $signUps,
            'byKind' => $facilities->groupBy('kind')->map->count()->sortDesc(),
            'kinds' => RegisteredUserController::FACILITY_KINDS,
            'settingUp' => $facilities->whereNull('onboarded_at')->sortByDesc('created_at')->values(),
            'owners' => $owners,
            'inactive' => $facilities->where('is_active', false)->values(),
            'withoutAdmin' => $facilities->where('is_active', true)->where('active_admins_count', 0)->values(),
            'busiest' => $facilities->where('is_active', true)->sortByDesc('visits_30d')->take(6)->values(),
            'recent' => $facilities->sortByDesc('created_at')->take(5)->values(),
        ]);
    }
}
