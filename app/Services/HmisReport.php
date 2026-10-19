<?php

namespace App\Services;

use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Diagnosis;
use App\Models\Payment;
use App\Models\Visit;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HmisReport
{
    /**
     * @return array{attendance: list<array{sex: string, band: string, new: int, reattendance: int}>, diagnoses: Collection<int, object>}
     */
    public function outpatient(CarbonInterface $start, CarbonInterface $end): array
    {
        $visits = Visit::query()
            ->with('patient')
            ->whereBetween('opened_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where('visit_type', '!=', VisitType::Inpatient)
            ->get();

        $bands = ['0-4 years', '5-14 years', '15 years and above'];
        $attendance = [];

        foreach (['female', 'male'] as $sex) {
            foreach ($bands as $band) {
                $attendance[] = [
                    'sex' => $sex,
                    'band' => $band,
                    'new' => 0,
                    'reattendance' => 0,
                ];
            }
        }

        foreach ($visits as $visit) {
            $age = $visit->patient->ageInYears();
            $band = match (true) {
                $age <= 4 => '0-4 years',
                $age <= 14 => '5-14 years',
                default => '15 years and above',
            };

            $prior = Visit::query()
                ->where('patient_id', $visit->patient_id)
                ->where('id', '<', $visit->id)
                ->exists();

            foreach ($attendance as $index => $row) {
                if ($row['sex'] === $visit->patient->sex->value && $row['band'] === $band) {
                    $attendance[$index][$prior ? 'reattendance' : 'new']++;
                }
            }
        }

        $diagnoses = Diagnosis::query()
            ->selectRaw('icd10_code, title, count(*) as total')
            ->whereHas('visit', function ($query) use ($start, $end): void {
                $query->whereBetween('opened_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
            })
            ->groupBy('icd10_code', 'title')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return [
            'attendance' => $attendance,
            'diagnoses' => $diagnoses,
        ];
    }

    /**
     * @return array{admissions: int, discharges: int, deaths: int, bed_days: int, occupancy: float, beds: int}
     */
    public function inpatient(CarbonInterface $start, CarbonInterface $end): array
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();

        $admissions = Admission::query()->whereBetween('admitted_at', [$from, $to])->count();
        $discharges = Admission::query()->whereBetween('discharged_at', [$from, $to])->count();
        $deaths = Admission::query()
            ->whereBetween('discharged_at', [$from, $to])
            ->where('discharge_outcome', 'died')
            ->count();

        $bedDays = Admission::query()
            ->where('admitted_at', '<=', $to)
            ->where(function ($query) use ($from): void {
                $query->whereNull('discharged_at')->orWhere('discharged_at', '>=', $from);
            })
            ->get()
            ->sum(function (Admission $admission) use ($from, $to): int {
                $startAt = $admission->admitted_at->greaterThan($from) ? $admission->admitted_at : $from;
                $endAt = $admission->discharged_at && $admission->discharged_at->lessThan($to) ? $admission->discharged_at : $to;

                return max(1, $startAt->diffInDays($endAt) ?: 1);
            });

        $beds = Bed::query()->count();
        $days = max(1, $from->diffInDays($to) + 1);
        $occupancy = $beds === 0 ? 0 : round(($bedDays / ($beds * $days)) * 100, 1);

        return [
            'admissions' => $admissions,
            'discharges' => $discharges,
            'deaths' => $deaths,
            'bed_days' => (int) $bedDays,
            'occupancy' => (float) $occupancy,
            'beds' => $beds,
        ];
    }

    public function revenue(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) Payment::query()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where('status', 'confirmed')
            ->sum('amount');
    }
}
