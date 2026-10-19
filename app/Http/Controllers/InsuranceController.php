<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\InsuranceScheme;
use App\Models\Preauthorisation;
use App\Models\Visit;
use App\Services\ClaimService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InsuranceController extends Controller
{
    public function __construct(private ClaimService $claims) {}

    public function index(): View
    {
        return view('insurance.index', [
            'schemes' => InsuranceScheme::query()->orderBy('name')->get(),
            'preauthorisations' => Preauthorisation::query()->with(['visit.patient', 'scheme'])->latest()->limit(30)->get(),
            'claims' => Claim::query()->with('scheme')->latest()->limit(20)->get(),
            'visits' => Visit::query()->with('patient')->whereNotNull('insurance_scheme_id')->latest('opened_at')->limit(30)->get(),
        ]);
    }

    public function storeScheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'kind' => ['required', Rule::in(['private', 'corporate'])],
            'cover_limit' => ['nullable', 'integer', 'min:0'],
            'requires_preauthorisation' => ['sometimes', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        InsuranceScheme::query()->create([
            ...$data,
            'facility_id' => $request->user()->facility_id,
            'requires_preauthorisation' => $request->boolean('requires_preauthorisation'),
            'is_active' => true,
        ]);

        return back()->with('status', 'Scheme saved.');
    }

    public function storePreauthorisation(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'visit_id' => ['required', Rule::exists('visits', 'id')->where('facility_id', $facilityId)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $visit = Visit::query()->findOrFail($data['visit_id']);

        Preauthorisation::query()->create([
            'facility_id' => $facilityId,
            'visit_id' => $visit->id,
            'insurance_scheme_id' => $visit->insurance_scheme_id,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'status' => 'pending',
            'requested_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Preauthorisation requested.');
    }

    public function decidePreauthorisation(Request $request, Preauthorisation $preauthorisation): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        $preauthorisation->update([
            'status' => $data['decision'],
            'reference' => $data['reference'] ?? null,
            'decided_at' => now(),
        ]);

        return back()->with('status', 'Preauthorisation '.$data['decision'].'.');
    }

    public function storeClaim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'insurance_scheme_id' => ['required', Rule::exists('insurance_schemes', 'id')->where('facility_id', $request->user()->facility_id)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $scheme = InsuranceScheme::query()->findOrFail($data['insurance_scheme_id']);
        $claim = $this->claims->compile($scheme, Carbon::parse($data['period_start']), Carbon::parse($data['period_end']));

        return back()->with('status', 'Claim '.$claim->number.' compiled.');
    }

    public function submitClaim(Claim $claim): RedirectResponse
    {
        $this->claims->submit($claim);

        return back()->with('status', 'Claim submitted.');
    }

    public function remit(Request $request, Claim $claim): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['required', 'string', 'max:80'],
        ]);

        $this->claims->remit($claim, $request->user(), (int) $data['amount'], $data['reference']);

        return back()->with('status', 'Remittance recorded.');
    }
}
