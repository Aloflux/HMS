<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingType;
use App\Http\Controllers\Controller;
use App\Models\ImagingStudy;
use App\Models\Item;
use App\Models\LabTest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function index(): View
    {
        return view('admin.catalogue.index', [
            'services' => Service::query()->orderBy('name')->get(),
            'items' => Item::query()->orderBy('name')->get(),
            'tests' => LabTest::query()->with('service')->orderBy('name')->get(),
            'studies' => ImagingStudy::query()->with('service')->orderBy('name')->get(),
            'billingTypes' => BillingType::cases(),
        ]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:30'],
            'cash_price' => ['required', 'integer', 'min:0'],
        ]);

        Service::query()->create([
            ...$data,
            'facility_id' => $request->user()->facility_id,
            'is_active' => true,
        ]);

        return back()->with('status', 'Service added to the price list.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(['medicine', 'consumable', 'reagent'])],
            'billing_type' => ['required', Rule::enum(BillingType::class)],
            'unit' => ['required', 'string', 'max:30'],
            'cash_price' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
        ]);

        Item::query()->create([
            ...$data,
            'facility_id' => $request->user()->facility_id,
            'is_active' => true,
        ]);

        return back()->with('status', 'Item added.');
    }

    public function storeLabTest(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'service_id' => ['required', Rule::exists('services', 'id')->where('facility_id', $facilityId)],
            'specimen' => ['nullable', 'string', 'max:40'],
            'unit' => ['nullable', 'string', 'max:30'],
            'reference_text' => ['nullable', 'string', 'max:255'],
            'reagent_item_id' => ['nullable', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'reagent_quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        LabTest::query()->create([
            ...$data,
            'facility_id' => $facilityId,
            'reagent_quantity' => $data['reagent_quantity'] ?? 1,
            'is_active' => true,
        ]);

        return back()->with('status', 'Lab test added.');
    }

    public function storeStudy(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'service_id' => ['required', Rule::exists('services', 'id')->where('facility_id', $facilityId)],
        ]);

        ImagingStudy::query()->create([
            ...$data,
            'facility_id' => $facilityId,
            'is_active' => true,
        ]);

        return back()->with('status', 'Imaging study added.');
    }
}
