<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PositionIndoorRoom
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
 * @property string                      $position_indoor_room_type_uuid
 * @property-read PositionIndoorRoomType $PositionIndoorRoomType
 * @property string                      $location_station_uuid
 * @property-read LocationStation        $LocationStation
 * @property string                      $location_section_uuid
 * @property-read LocationSection        $LocationSection
 * @property string                      $location_center_uuid
 * @property-read LocationCenter         $LocationCenter
 * @property string                      $location_railroad_uuid
 * @property-read LocationRailroad       $LocationRailroad
 * @property-read PositionIndoorRow[]    $PositionIndoorRows
 */
class PositionIndoorRoom extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 所属室内上道位置机房类型
	 *
	 * @return HasOne
	 */
	public function PositionIndoorRoomType(): HasOne
	{
		return $this->hasOne(PositionIndoorRoomType::class, "uuid", "position_indoor_room_type_uuid");
	}
	
	/**
	 * 所属站场
	 *
	 * @return HasOne
	 */
	public function LocationStation(): HasOne
	{
		return $this->hasOne(LocationStation::class, "uuid", "location_station_uuid");
	}
	
	/**
	 * 所属区间
	 *
	 * @return HasOne
	 */
	public function LocationSection(): HasOne
	{
		return $this->hasOne(LocationSection::class, "uuid", "location_section_uuid");
	}
	
	/**
	 * 所属中心
	 *
	 * @return HasOne
	 */
	public function LocationCenter(): HasOne
	{
		return $this->hasOne(LocationCenter::class, "uuid", "location_center_uuid");
	}
	
	/**
	 * 所属道口
	 *
	 * @return HasOne
	 */
	public function locationRailroad(): HasOne
	{
		return $this->hasOne(LocationRailroad::class, "uuid", "location_railroad_uuid");
	}
	
	/**
	 * 相关室内上道位置机房排
	 *
	 * @return BelongsTo
	 */
	public function PositionIndoorRows(): BelongsTo
	{
		return $this->belongsTo(PositionIndoorRow::class, "position_indoor_row_uuid", "uuid");
	}
}
