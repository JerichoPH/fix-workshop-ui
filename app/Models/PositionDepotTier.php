<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class PositionDepotTier
 *
 * @package App\Models
 * @property int                       $id
 * @property Carbon                    $created_at
 * @property Carbon                    $updated_at
 * @property Carbon|null               $deleted_at
 * @property string                    $uuid
 * @property int                       $sort
 * @property string                    $unique_code
 * @property string                    $name
 * @property string                    $position_depot_cabinet_uuid
 * @property-read PositionDepotCabinet $position_depot_cabinet
 * @property-read PositionDepotCell[]  $position_depot_cells
 */
class PositionDepotTier extends Model
{
	/**
	 * 所属机柜
	 *
	 * @return HasOne
	 */
	public function PositionDepotCabinet(): HasOne
	{
		return $this->hasOne(PositionDepotCabinet::class, "uuid", "position_depot_cabinet_uuid");
	}
	
	public function PositionDepotCells(): BelongsTo
	{
		return $this->belongsTo(PositionDepotCell::class, "position_depot_tier_uuid", "uuid");
	}
}
