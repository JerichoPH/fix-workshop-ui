<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PivotLocationLineAndLocationSection extends Model
{
    protected $guarded = [];

    /**
     * 所属线别
     * @return HasOne
     */
    public function LocationLine(): HasOne
    {
        return $this->hasOne(LocationLine::class);
    }

    /**
     * 所属区间
     * @return HasOne
     */
    public function LocationSection(): HasOne
    {
        return $this->hasOne(LocationSection::class);
    }
}
