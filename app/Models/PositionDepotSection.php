<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class PositionDepotSection
 *
 * @package App\Models
 * @property int                          $id
 * @property Carbon                       $created_at
 * @property Carbon                       $updated_at
 * @property Carbon|null                  $deleted_at
 * @property string                       $uuid
 * @property int                          $sort
 * @property string                       $unique_code
 * @property string                       $name
 * @property string                       $position_depot_storehouse_uuid
 * @property-read PositionDepotStorehouse $PositionDepotStorehouse
 */
class PositionDepotSection extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return "{$this->PositionDepotStorehouse} {$this->attributes['name']}";
	}
	
	/**
	 * 所属仓库
	 *
	 * @return HasOne
	 */
	public function PositionDepotStorehouse(): HasOne
	{
		return $this->hasOne(PositionDepotStorehouse::class, "uuid", "position_depot_storehouse_uuid");
	}
}
