<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class PositionIndoorTier
 *
 * @package App\Models
 * @property int                        $id
 * @property Carbon                     $created_at
 * @property Carbon                     $updated_at
 * @property Carbon|null                $deleted_at
 * @property string                     $uuid
 * @property int                        $sort
 * @property string                     $unique_code
 * @property string                     $name
 * @property string                     $position_indoor_cabinet_uuid
 * @property-read PositionIndoorCabinet $position_indoor_cabinet
 */
class PositionIndoorTier extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 所属室内上道位置机柜
	 *
	 * @return HasOne
	 */
	public function PositionIndoorCabinet(): HasOne
	{
		return $this->hasOne(PositionIndoorCabinet::class, "uuid", "position_indoor_cabinet_uuid");
	}
	
	/**
	 * 相关室内上道位置机柜格位
	 *
	 * @return BelongsTo
	 */
	public function PositionIndoorCells(): BelongsTo
	{
		return $this->belongsTo(PositionIndoorCell::class, "uuid", "position_indoor_tier_uuid");
	}
}
