<?php

namespace App\Http\Controllers;

use App\Models\Dispensation;
use App\Models\WardConsumption;
use App\Services\HmisReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private HmisReport $reports) {}

    public function index(Request $request): View
    {
        [$start, $end] = $this->range($request);

        $unbilled = Dispensation::query()->whereNull('bill_line_id')->count();
        $wardUse = WardConsumption::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return view('reports.index', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'revenue' => $this->reports->revenue($start, $end),
            'outpatient' => $this->reports->outpatient($start, $end),
            'inpatient' => $this->reports->inpatient($start, $end),
            'unbilledDispenses' => $unbilled,
            'wardIssuesThisWeek' => $wardUse,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->range($request);
        $outpatient = $this->reports->outpatient($start, $end);
        $inpatient = $this->reports->inpatient($start, $end);

        return response()->streamDownload(function () use ($start, $end, $outpatient, $inpatient): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['dataset', 'period', 'key', 'value']);
            fputcsv($handle, ['HMIS108', $start->format('Y-m'), 'admissions', $inpatient['admissions']]);
            fputcsv($handle, ['HMIS108', $start->format('Y-m'), 'discharges', $inpatient['discharges']]);
            fputcsv($handle, ['HMIS108', $start->format('Y-m'), 'deaths', $inpatient['deaths']]);
            fputcsv($handle, ['HMIS108', $start->format('Y-m'), 'bed_days', $inpatient['bed_days']]);

            foreach ($outpatient['attendance'] as $row) {
                fputcsv($handle, ['HMIS105', $start->format('Y-m'), $row['sex'].' '.$row['band'].' new', $row['new']]);
                fputcsv($handle, ['HMIS105', $start->format('Y-m'), $row['sex'].' '.$row['band'].' reattendance', $row['reattendance']]);
            }

            foreach ($outpatient['diagnoses'] as $diagnosis) {
                fputcsv($handle, ['HMIS105', $end->format('Y-m'), $diagnosis->icd10_code, $diagnosis->total]);
            }

            fclose($handle);
        }, 'hmis-'.$start->format('Y-m').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $start = $request->filled('start') ? Carbon::parse($request->string('start')->toString()) : now()->startOfMonth();
        $end = $request->filled('end') ? Carbon::parse($request->string('end')->toString()) : now()->endOfMonth();

        return [$start, $end];
    }
}
