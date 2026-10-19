<?php

namespace App\Models\Concerns;

use App\Models\Facility;
use App\Support\FacilityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToFacility
{
    public static function bootBelongsToFacility(): void
    {
        static::addGlobalScope('facility', function (Builder $query): void {
            $facilityId = FacilityContext::id();

            if ($facilityId !== null) {
                $query->where($query->getModel()->getTable().'.facility_id', $facilityId);

                return;
            }

            if (FacilityContext::shouldHideTenantData()) {
                $query->whereRaw('0 = 1');
            }
        });

        static::creating(function (Model $model): void {
            if (! $model->getAttribute('facility_id') && FacilityContext::id() !== null) {
                $model->setAttribute('facility_id', FacilityContext::id());
            }
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
