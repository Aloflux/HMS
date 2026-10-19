<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'key',
        'last_value',
    ];
}
