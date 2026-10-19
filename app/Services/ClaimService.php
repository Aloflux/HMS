<?php

namespace App\Services;

use App\Enums\Payer;
use App\Models\BillLine;
use App\Models\Claim;
use App\Models\ClaimLine;
use App\Models\InsuranceScheme;
use App\Models\Remittance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimService
{
    public function __construct(private SequenceGenerator $sequences) {}

    public function compile(InsuranceScheme $scheme, CarbonInterface $start, CarbonInterface $end): Claim
    {
        return DB::transaction(function () use ($scheme, $start, $end): Claim {
            $payer = $scheme->kind === 'corporate' ? Payer::Corporate : Payer::Insurance;

            $claimedIds = ClaimLine::query()->pluck('bill_line_id');

            $lines = BillLine::query()
                ->where('payer', $payer)
                ->where('waived', false)
                ->whereNotIn('id', $claimedIds)
                ->whereHas('bill', function ($query) use ($scheme, $start, $end): void {
                    $query->whereHas('visit', function ($visit) use ($scheme, $start, $end): void {
                        $visit->where('insurance_scheme_id', $scheme->id)
                            ->whereBetween('opened_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
                    });
                })
                ->get();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'period_start' => 'No unclaimed charges were found for that period.',
                ]);
            }

            $claim = Claim::query()->create([
                'facility_id' => $scheme->facility_id,
                'insurance_scheme_id' => $scheme->id,
                'number' => $this->sequences->next($scheme->facility, 'claim'),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => 'draft',
                'total_amount' => (int) $lines->sum('amount'),
            ]);

            foreach ($lines as $line) {
                $claim->lines()->create([
                    'facility_id' => $scheme->facility_id,
                    'bill_line_id' => $line->id,
                    'amount' => $line->amount,
                ]);
            }

            return $claim;
        });
    }

    public function submit(Claim $claim): void
    {
        if ($claim->status !== 'draft') {
            throw ValidationException::withMessages([
                'claim' => 'Only a draft claim can be submitted.',
            ]);
        }

        $claim->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function remit(Claim $claim, User $actor, int $amount, string $reference): Remittance
    {
        return DB::transaction(function () use ($claim, $actor, $amount, $reference): Remittance {
            if (! in_array($claim->status, ['submitted', 'remitted'], true)) {
                throw ValidationException::withMessages([
                    'claim' => 'Submit the claim before recording a remittance.',
                ]);
            }

            $remittance = Remittance::query()->create([
                'facility_id' => $claim->facility_id,
                'claim_id' => $claim->id,
                'amount' => $amount,
                'reference' => $reference,
                'received_at' => now(),
                'received_by' => $actor->id,
            ]);

            $claim->increment('remitted_amount', $amount);
            $claim->update([
                'status' => $claim->fresh()->remitted_amount >= $claim->total_amount ? 'remitted' : 'submitted',
            ]);

            return $remittance;
        });
    }
}
