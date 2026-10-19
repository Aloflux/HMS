<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Item;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $today = now()->startOfDay();

        $visits = Visit::query()->where('opened_at', '>=', $today);
        $lowStock = Item::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->withSum(['batches as main_quantity' => function ($query): void {
                $query->whereHas('location', fn ($location) => $location->where('kind', 'main'));
            }], 'quantity')
            ->get()
            ->filter(fn (Item $item): bool => (int) $item->main_quantity <= $item->reorder_level);

        return view('dashboard', [
            'visitsToday' => (clone $visits)->count(),
            'admitted' => Admission::query()->where('status', 'admitted')->count(),
            'waiting' => QueueEntry::query()->where('status', 'waiting')->count(),
            'revenueToday' => (int) Payment::query()->where('created_at', '>=', $today)->sum('amount'),
            'beds' => Bed::query()->count(),
            'occupiedBeds' => Bed::query()->where('status', 'occupied')->count(),
            'lowStock' => $lowStock,
            'recentVisits' => Visit::query()->with('patient')->latest('opened_at')->limit(8)->get(),
        ]);
    }
}
