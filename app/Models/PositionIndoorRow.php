<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class PositionIndoorRow
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $unique_code
 * @property string $name
 * @property string $position_indoor_room_uuid
 * @property-read PositionIndoorRoom $position_indoor_room
 */
class PositionIndoorRow extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属室内上道位置机房
     * @return HasOne
     */
    public function PositionIndoorRoom(): HasOne
    {
        return $this->hasOne(PositionIndoorRoom::class, "uuid", "position_indoor_room_uuid");
    }

    /**
     * 相关室内上道位置柜架
     * @return BelongsTo
     */
    public function PositionIndoorCabinets(): BelongsTo
    {
        return $this->belongsTo(PositionIndoorRow::class, "position_indoor_row_uuid", "uuid");
    }
}
