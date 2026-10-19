<?php

namespace App\Http\Controllers;

use App\Models\ImagingOrder;
use App\Services\EncounterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RadiologyController extends Controller
{
    public function index(): View
    {
        return view('radiology.index', [
            'orders' => ImagingOrder::query()->with(['study', 'visit.patient'])->latest()->limit(50)->get(),
        ]);
    }

    public function report(Request $request, ImagingOrder $order, EncounterService $encounters): RedirectResponse
    {
        $data = $request->validate([
            'report' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('imaging', 'local');
        }

        $encounters->reportImaging($order, $request->user(), $data);

        return back()->with('status', 'Imaging report saved.');
    }
}
