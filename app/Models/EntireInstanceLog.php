<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class EntireInstanceLog
 *
 * @package App\Models
 * @property int                        $id
 * @property Carbon                     $created_at
 * @property Carbon                     $updated_at
 * @property Carbon|null                $deleted_at
 * @property string                     $uuid
 * @property int                        $sort
 * @property string                     $entire_instance_log_type_unique_code
 * @property EntireInstanceLogType      $entire_instance_log_type
 * @property string                     $name
 * @property string                     $url
 * @property string                     $operator_uuid
 * @property-read Account               $operator
 * @property string                     $entire_instance_identity_code
 * @property-read EntireInstance        $entire_instance
 * @property string                     $organization_railway_uuid
 * @property-read OrganizationRailway   $organization_railway
 * @property string                     $organization_paragraph_uuid
 * @property-read OrganizationParagraph $organization_paragraph
 * @property string                     $organization_workshop_uuid
 * @property-read OrganizationWorkshop  $organization_workshop
 * @property string                     $organization_work_area_uuid
 * @property-read OrganizationWorkArea  $organization_work_area
 * @property string                     $location_line_uuid
 * @property-read LocationLine          $location_line
 * @property string                     $location_station_uuid
 * @property-read LocationStation       $location_station
 * @property string                     $location_section_uuid
 * @property-read LocationSection       $location_section
 * @property string                     $location_center_uuid
 * @property-read LocationCenter        $location_center
 * @property string                     $location_railroad_uuid
 * @property-read LocationRailroad      $location_railroad
 */
class EntireInstanceLog extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 日志类型
	 *
	 * @return HasOne
	 */
	public function EntireInstanceLogType(): HasOne
	{
		return $this->hasOne(EntireInstanceLogType::class, "unique_code", "entire_instance_log_type_unique_code");
	}
	
	/**
	 * 所属用户
	 *
	 * @return HasOne
	 */
	public function Operator(): Hasone
	{
		return $this->hasOne(Account::class, "uuid", "operator_uuid");
	}
	
	/**
	 * 所属器材
	 *
	 * @return HasOne
	 */
	public function EntireInstance(): HasOne
	{
		return $this->hasOne(EntireInstance::class, "identity_code", "entire_instance_identity_code");
	}
	
	/**
	 * 所属路局
	 *
	 * @return HasOne
	 */
	public function OrganizationRailway(): HasOne
	{
		return $this->hasOne(OrganizationRailway::class, "uuid", "organization_railway_uuid");
	}
	
	/**
	 * 所属站段
	 *
	 * @return HasOne
	 */
	public function OrganizationParagraph(): HasOne
	{
		return $this->hasOne(OrganizationParagraph::class, "uuid", "organization_paragraph_uuid");
	}
	
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
		return $this->hasOne(OrganizationWorkArea::class, "uuid", "organization_work_area_uuid");
	}
	
	/**
	 * 所属线别
	 *
	 * @return HasOne
	 */
	public function LocationLine(): HasOne
	{
		return $this->hasOne(LocationLine::class, "uuid", "location_line_uuid");
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
	public function LocationRailroadGradCross(): HasOne
	{
		return $this->hasOne(LocationRailroad::class, "uuid", "location_railroad_uuid");
	}
	
}
