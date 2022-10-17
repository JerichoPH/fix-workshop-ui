<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PositionDepotRow
 *
 * @package App\Models
 * @property string                      $id
 * @property string                      $created_at
 * @property string                      $updated_at
 * @property string                      $deleted_at
 * @property string                      $uuid
 * @property string                      $sort
 * @property string                      $unique_code
 * @property string                      $name
 * @property string                      $position_depot_row_type_uuid
 * @property-read PositionDepotRowType   $position_depot_row_type
 * @property string                      $position_depot_section_uuid
 * @property-read PositionDepotSection   $position_depot_section
 * @property-read PositionDepotCabinet[] $position_depot_cabinets
 */
class PositionDepotRow extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 所属仓库排类型
	 *
	 * @return HasOne
	 */
	public function PositionDepotRowType(): HasOne
	{
		return $this->hasOne(PositionDepotRowType::class, "uuid", "position_depot_row_type_uuid");
	}
	
	/**
	 * 所属仓库区域
	 *
	 * @return HasOne
	 */
	public function PositionDepotSection(): HasOne
	{
		return $this->hasOne(PositionDepotSection::class, "uuid", "position_depot_section_uuid");
	}
	
	/**
	 * 相关机柜
	 *
	 * @return BelongsTo
	 */
	public function PositionDepotCabinets(): BelongsTo
	{
		return $this->belongsTo(PositionDepotCabinet::class, "position_depot_cabinet_uuid", "uuid");
	}
}
