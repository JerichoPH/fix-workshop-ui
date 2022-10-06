<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class
 * @package App\Models
 * @property string $location_line_uuid
 * @property-read LocationLine $location_line
 * @property string $location_center_uuid
 * @property-read LocationCenter $location_center
 */
class PivotLocationLineAndLocationCenter extends Model
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
     * 所属中心
     * @return HasOne
     */
    public function LocationCenter(): HasOne
    {
        return $this->hasOne(LocationCenter::class, 'uuid', 'location_center_uuid');
    }
}
