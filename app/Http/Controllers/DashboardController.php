<?php

namespace App\Http\Controllers;

use App\Enums\QueueStage;
use App\Models\Admission;
use App\Models\Appointment;
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
        $beds = Bed::query()->where('status', '!=', 'closed')->count();
        $occupiedBeds = Bed::query()->where('status', 'occupied')->count();
        $waitingByStage = QueueEntry::query()
            ->where('status', 'waiting')
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');
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
            'beds' => $beds,
            'occupiedBeds' => $occupiedBeds,
            'occupancy' => $beds > 0 ? (int) round(($occupiedBeds / $beds) * 100) : 0,
            'freeBeds' => max($beds - $occupiedBeds, 0),
            'lowStock' => $lowStock->take(4),
            'queues' => collect(QueueStage::cases())->map(fn (QueueStage $stage): array => [
                'label' => $stage->label(),
                'count' => (int) ($waitingByStage[$stage->value] ?? 0),
            ])->all(),
            'appointments' => Appointment::query()
                ->with(['patient', 'department'])
                ->whereBetween('scheduled_at', [$today, now()->endOfDay()])
                ->orderBy('scheduled_at')
                ->limit(6)
                ->get(),
            'recentVisits' => Visit::query()->with(['patient', 'department'])->latest('opened_at')->limit(6)->get(),
        ]);
    }
}
