<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    use BelongsToFacility, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'department_id', 'name', 'code',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }
}
