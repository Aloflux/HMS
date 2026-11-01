<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\Bed;
use App\Models\Delivery;
use App\Models\Department;
use App\Models\Facility;
use App\Models\ImagingOrder;
use App\Models\InsuranceScheme;
use App\Models\Item;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\StockBatch;
use App\Models\TheatreBooking;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\FacilityProvisioner;
use App\Support\FacilityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FacilityController extends Controller
{
    /**
     * Weeks of visit history shown on a facility's trend chart.
     */
    private const TREND_WEEKS = 12;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['active', 'inactive', 'setup'], true) ? $request->query('status') : 'all';
        $sort = $request->query('sort') === 'newest' ? 'newest' : 'name';

        $facilities = Facility::query()
            ->withCount([
                'patients' => fn ($query) => $query->withoutGlobalScope('facility'),
                'users as admins_count' => fn ($query) => $query->where('role', Role::Administrator),
                'users as active_admins_count' => fn ($query) => $query->where('role', Role::Administrator)->where('is_active', true),
                'users as staff_count',
            ])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('code', "%{$search}%")
                ->orWhereLike('district', "%{$search}%")))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true)->whereNotNull('onboarded_at'))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'setup', fn ($query) => $query->whereNull('onboarded_at'))
            ->when($sort === 'newest', fn ($query) => $query->latest(), fn ($query) => $query->orderBy('name'))
            ->get();

        $all = Facility::query()->get(['id', 'is_active', 'onboarded_at']);

        return view('platform.facilities.index', [
            'facilities' => $facilities,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'counts' => [
                'all' => $all->count(),
                'active' => $all->where('is_active', true)->whereNotNull('onboarded_at')->count(),
                'setup' => $all->whereNull('onboarded_at')->count(),
                'inactive' => $all->where('is_active', false)->count(),
            ],
            'kinds' => RegisteredUserController::FACILITY_KINDS,
            'openProvision' => $request->boolean('provision') || (bool) session('errors')?->hasAny(['name', 'code', 'kind', 'district', 'bed_capacity', 'admin_name', 'admin_email', 'admin_password']),
        ]);
    }

    /**
     * One facility's profile, health and activity. Aggregate counts only; no patient record is ever loaded.
     */
    public function show(Facility $facility): View
    {
        $scoped = fn (string $model): Builder => $model::query()->withoutGlobalScope('facility')->where('facility_id', $facility->id);
        $since30d = now()->subDays(30);

        $staff = $facility->users()->get(['id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'email_verified_at']);
        $beds = $scoped(Bed::class)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $bedsInService = (int) $beds->except('closed')->sum();

        $weekStart = now()->startOfWeek()->subWeeks(self::TREND_WEEKS - 1);
        $visitsByWeek = $scoped(Visit::class)
            ->where('opened_at', '>=', $weekStart)
            ->pluck('opened_at')
            ->countBy(fn (mixed $openedAt): string => Carbon::parse($openedAt)->startOfWeek()->toDateString());
        $visitTrend = collect(range(0, self::TREND_WEEKS - 1))->map(function (int $offset) use ($weekStart, $visitsByWeek): array {
            $from = $weekStart->copy()->addWeeks($offset);

            return ['label' => $from->format('d M'), 'count' => (int) ($visitsByWeek[$from->toDateString()] ?? 0)];
        });

        $stockOnHand = $scoped(StockBatch::class)->selectRaw('item_id, sum(quantity) as on_hand')->groupBy('item_id')->pluck('on_hand', 'item_id');
        $lowStock = $scoped(Item::class)
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->get(['id', 'reorder_level'])
            ->filter(fn (Item $item): bool => (int) ($stockOnHand[$item->id] ?? 0) <= $item->reorder_level)
            ->count();

        $lastActivity = collect([
            $scoped(AuditLog::class)->max('created_at'),
            $staff->max('last_login_at'),
        ])->filter()->map(fn (mixed $at): Carbon => Carbon::parse($at))->max();

        return view('platform.facilities.show', [
            'facility' => $facility,
            'kinds' => RegisteredUserController::FACILITY_KINDS,
            'owner' => $staff->where('role', Role::Administrator)->sortBy('id')->first(),
            'admins' => $staff->where('role', Role::Administrator)->sortBy('name')->values(),
            'lastActivity' => $lastActivity,
            'patients' => [
                'total' => $scoped(Patient::class)->count(),
                'new30d' => $scoped(Patient::class)->where('created_at', '>=', $since30d)->count(),
            ],
            'visits' => [
                'today' => $scoped(Visit::class)->where('opened_at', '>=', today())->count(),
                'last30d' => $scoped(Visit::class)->where('opened_at', '>=', $since30d)->count(),
                'open' => $scoped(Visit::class)->whereNull('closed_at')->count(),
                'trend' => $visitTrend,
                'byPayer' => $scoped(Visit::class)->where('opened_at', '>=', $since30d)->selectRaw('payer, count(*) as total')->groupBy('payer')->pluck('total', 'payer')->sortDesc(),
                'byType' => $scoped(Visit::class)->where('opened_at', '>=', $since30d)->selectRaw('visit_type, count(*) as total')->groupBy('visit_type')->pluck('total', 'visit_type')->sortDesc(),
            ],
            'revenue' => [
                'today' => (int) $scoped(Payment::class)->where('status', 'confirmed')->where('created_at', '>=', today())->sum('amount'),
                'last30d' => (int) $scoped(Payment::class)->where('status', 'confirmed')->where('created_at', '>=', $since30d)->sum('amount'),
            ],
            'beds' => [
                'planned' => $facility->bed_capacity,
                'inService' => $bedsInService,
                'occupied' => (int) ($beds['occupied'] ?? 0),
                'admitted' => $scoped(Admission::class)->where('status', 'admitted')->count(),
            ],
            'clinical' => [
                'Admissions' => $scoped(Admission::class)->where('admitted_at', '>=', $since30d)->count(),
                'Lab requests' => $scoped(LabRequest::class)->where('created_at', '>=', $since30d)->count(),
                'Imaging orders' => $scoped(ImagingOrder::class)->where('created_at', '>=', $since30d)->count(),
                'Prescriptions' => $scoped(Prescription::class)->where('created_at', '>=', $since30d)->count(),
                'Theatre bookings' => $scoped(TheatreBooking::class)->where('scheduled_at', '>=', $since30d)->count(),
                'Deliveries' => $scoped(Delivery::class)->where('delivered_at', '>=', $since30d)->count(),
            ],
            'staff' => [
                'total' => $staff->count(),
                'active' => $staff->where('is_active', true)->count(),
                'signedIn7d' => $staff->filter(fn (User $user): bool => $user->last_login_at?->greaterThanOrEqualTo(now()->subDays(7)) ?? false)->count(),
                'neverSignedIn' => $staff->where('is_active', true)->whereNull('last_login_at')->count(),
                'byRole' => $staff->where('is_active', true)->groupBy(fn (User $user): string => $user->role->label())->map->count()->sortDesc(),
            ],
            'setup' => [
                'Departments' => $scoped(Department::class)->where('is_active', true)->count(),
                'Wards' => $scoped(Ward::class)->count(),
                'Insurance schemes' => $scoped(InsuranceScheme::class)->where('is_active', true)->count(),
                'Stock items' => $scoped(Item::class)->where('is_active', true)->count(),
            ],
            'stock' => [
                'low' => $lowStock,
                'expired' => $scoped(StockBatch::class)->where('quantity', '>', 0)->whereDate('expiry_date', '<', today())->count(),
                'expiringSoon' => $scoped(StockBatch::class)->where('quantity', '>', 0)->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(60))->count(),
            ],
        ]);
    }

    public function store(Request $request, FacilityProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:12', 'unique:facilities,code'],
            'kind' => ['required', Rule::in(['hospital', 'clinic', 'faith_based'])],
            'district' => ['required', 'string', 'max:100'],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['required', Password::defaults()],
        ]);

        $hide = FacilityContext::shouldHideTenantData();
        $previous = FacilityContext::id();

        $provisioner->provision([
            'name' => $data['name'],
            'code' => $data['code'],
            'kind' => $data['kind'],
            'district' => $data['district'],
            'bed_capacity' => (int) $data['bed_capacity'],
        ], [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
        ]);

        FacilityContext::set($previous, $hide);

        return back()->with('status', 'Facility provisioned with departments, stores, wards and an administrator.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $facility->update($data);

        return back()->with('status', $facility->name.($facility->is_active ? ' is active again.' : ' is now inactive. Its staff cannot sign in.'));
    }
}
