<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PivotLocationLineAndLocationRailroadGradeCross extends Model
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
     * 所属道口
     * @return HasOne
     */
    public function LocationRailroadGradeCross(): HasOne
    {
        return $this->hasOne(LocationRailroadGradeCross::class);
    }
}
