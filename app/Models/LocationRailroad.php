<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class locationRailroad
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
 * @property boolean                   $be_enable
 * @property string                    $organization_workshop_uuid
 * @property-read OrganizationWorkshop $organization_workshop
 * @property string                    $organization_work_area_uuid
 * @property-read OrganizationWorkArea $organization_work_area
 * @property-read LocationLine         $location_line
 * @property-read PositionIndoorRoom[] $position_indoor_rooms
 * @property-read EntireInstanceLog[]  $entire_instance_logs
 */
class LocationRailroad extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 所属车间
	 *
	 * @return HasOne
	 */
	public function OrganizationWorkshop(): HasOne
	{
		return $this->hasOne(OrganizationWorkshop::class, "uuid", "organization_workshop_uuid");
	}
	
	/**
	 * 所属工区
	 *
	 * @return HasOne
	 */
	public function OrganizationWorkArea(): HasOne
	{
		return $this->hasOne(OrganizationWorkshop::class, "uuid", "organization_work_area_uuid");
	}
	
	/**
	 * 所属线别
	 *
	 * @return HasOne
	 */
	public function LocationLine(): HasOne
	{
		return $this->hasOne(LocationLine::class, 'uuid', 'location_line_uuid');
	}
	
	/**
	 * 相关室内上道位置机房
	 *
	 * @return BelongsTo
	 */
	public function PositionIndoorRooms(): BelongsTo
	{
		return $this->belongsTo(PositionIndoorRoom::class, "location_railroad_uuid", "uuid");
	}
	
	/**
	 * 相关器材日志
	 *
	 * @return BelongsTo
	 */
	public function EntireInstanceLogs(): BelongsTo
	{
		return $this->belongsTo(EntireInstanceLog::class, "location_railroad_uuid", "uuid");
	}
}
