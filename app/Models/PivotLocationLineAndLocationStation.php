<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotLocationLineAndLocationStation
 * @package App\Models
 * @property string $location_line_uuid
 * @property-read LocationLine $location_line
 * @property string $location_station_uuid
 * @property-read LocationStation $location_station
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
        return $this->hasOne(LocationLine::class,'uuid','location_line_uuid');
    }

    /**
     * 所属站场
     * @return HasOne
     */
    public function LocationStation(): HasOne
    {
        return $this->hasOne(LocationStation::class,'uuid','location_station_uuid');
    }
}
