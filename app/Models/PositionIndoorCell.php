<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PositionIndoorCell
 *
 * @package App\Models
 * @property string                  $id
 * @property string                  $created_at
 * @property string                  $updated_at
 * @property string                  $deleted_at
 * @property string                  $uuid
 * @property string                  $sort
 * @property string                  $unique_code
 * @property string                  $name
 * @property string                  $position_indoor_tier_uuid
 * @property-read PositionIndoorTier $PositionIndoorTier
 */
class PositionIndoorCell extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return "{$this->PositionIndoorTier} {$this->attributes['name']}";
	}
	
	/**
	 * 所属室内上道位置机柜层
	 *
	 * @return HasOne
	 */
	public function PositionIndoorTier(): HasOne
	{
		return $this->hasOne(PositionIndoorTier::class, "uuid", "position_indoor_tier_uuid");
	}
}
