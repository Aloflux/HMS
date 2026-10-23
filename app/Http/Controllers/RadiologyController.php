<?php

namespace App\Http\Controllers;

use App\Models\ImagingOrder;
use App\Services\EncounterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RadiologyController extends Controller
{
    public function index(): View
    {
        return view('radiology.index', [
            'orders' => ImagingOrder::query()
                ->with(['study', 'visit.patient', 'reporter'])
                ->orderByRaw("CASE WHEN status = 'ordered' THEN 0 ELSE 1 END")
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function image(ImagingOrder $order): StreamedResponse
    {
        abort_unless($order->image_path && Storage::disk('local')->exists($order->image_path), 404);

        return Storage::disk('local')->response($order->image_path);
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
