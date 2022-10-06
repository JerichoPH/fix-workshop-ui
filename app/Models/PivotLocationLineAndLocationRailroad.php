<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotLocationLineAndLocationRailroad
 * @package App\Models
 * @property string $location_line_uuid
 * @property-read LocationLine $location_line
 * @property string $location_railroad_uuid
 * @property-read LocationRailroad $location_railroad
 */
class PivotLocationLineAndLocationRailroad extends Model
{
    protected $guarded = [];

    /**
     * 所属线别
     * @return HasOne
     */
    public function LocationLine(): HasOne
    {
        return $this->hasOne(LocationLine::class, 'uuid', 'location_line_uuid');
    }

    /**
     * 所属道口
     * @return HasOne
     */
    public function LocationRailroadGradeCross(): HasOne
    {
        return $this->hasOne(LocationRailroad::class, 'uuid', 'location_railroad_uuid');
    }
}
