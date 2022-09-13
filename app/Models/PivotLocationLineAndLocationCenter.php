<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PivotLocationLineAndLocationCenter extends Model
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
     * 所属中心
     * @return HasOne
     */
    public function LocationCenter(): HasOne
    {
        return $this->hasOne(LocationCenter::class);
    }
}
