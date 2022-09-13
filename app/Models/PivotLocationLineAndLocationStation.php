<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotLocationLineAndLocationStation
 * @package App\Models
 * @property int $location_line_id
 * @property int $location_station_id
 * @property LocationLine $location_line
 * @property LocationStation $location_station
 */
class PivotLocationLineAndLocationStation extends Model
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
     * 所属站场
     * @return HasOne
     */
    public function LocationStation(): HasOne
    {
        return $this->hasOne(LocationStation::class);
    }
}
