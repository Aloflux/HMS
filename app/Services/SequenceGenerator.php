<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\NumberSequence;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SequenceGenerator
{
    public function next(Facility $facility, string $key): string
    {
        $value = DB::transaction(function () use ($facility, $key): int {
            $sequence = NumberSequence::query()
                ->where('facility_id', $facility->id)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                try {
                    $sequence = NumberSequence::query()->create([
                        'facility_id' => $facility->id,
                        'key' => $key,
                        'last_value' => 0,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $sequence = NumberSequence::query()
                        ->where('facility_id', $facility->id)
                        ->where('key', $key)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $sequence->last_value++;
            $sequence->save();

            return $sequence->last_value;
        });

        return sprintf('%s-%s-%05d', $facility->code, now()->format('Y'), $value);
    }
}
