<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PositionIndoorCabinet
 * @package App\Models
 * @property string $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property string $uuid
 * @property string $sort
 * @property string $unique_code
 * @property string $name
 * @property string $position_indoor_row_uuid
 * @property-read PositionIndoorRow $position_indoor_row
 */
class PositionIndoorCabinet extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属室内上道位置机房排
     * @return HasOne
     */
    public function PositionIndoorRow(): HasOne
    {
        return $this->hasOne(PositionIndoorRow::class, "uuid", "position_indoor_row_uuid");
    }
}
