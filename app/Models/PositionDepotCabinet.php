<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class PositionDepotCabinet
 *
 * @package App\Models
 * @property int                      $id
 * @property Carbon                   $created_at
 * @property Carbon                   $updated_at
 * @property Carbon|null              $deleted_at
 * @property string                   $uuid
 * @property int                      $sort
 * @property string                   $unique_code
 * @property string                   $name
 * @property string                   $position_depot_row_uuid
 * @property-read PositionDepotRow    $PositionDepotRow
 * @property-read PositionDepotTier[] $PositionDepotTiers
 */
class PositionDepotCabinet extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return "{$this->PositionDepotRow} {$this->attributes['name']}";
	}
	
	/**
	 * 所属仓库排
	 *
	 * @return HasOne
	 */
	public function PositionDepotRow(): HasOne
	{
		return $this->hasOne(PositionDepotRow::class, "uuid", "position_depot_row_uuid");
	}
	
	/**
	 * 相关仓库层
	 *
	 * @return BelongsTo
	 */
	public function PositionDepotTiers(): BelongsTo
	{
		return $this->belongsTo(PositionDepotTier::class, "position_depot_tier_uuid", "uuid");
	}
}
