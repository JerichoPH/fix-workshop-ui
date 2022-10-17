<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class PositionDepotStorehouse
 *
 * @package App\Models
 * @property int                         $id
 * @property Carbon                      $created_at
 * @property Carbon                      $updated_at
 * @property Carbon|null                 $deleted_at
 * @property string                      $uuid
 * @property int                         $sort
 * @property string                      $unique_code
 * @property string                      $name
 * @property string                      $organization_workshop_uuid
 * @property-read OrganizationWorkshop   $OrganizationWorkshop
 * @property string                      $organization_work_area_uuid
 * @property-read OrganizationWorkArea   $OrganizationWorkArea
 * @property-read PositionDepotSection[] $PositionDepotSections
 */
class PositionDepotStorehouse extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
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
	 * 相关仓库区域
	 *
	 * @return BelongsTo
	 */
	public function PositionDepotSections(): BelongsTo
	{
		return $this->belongsTo(PositionDepotSection::class, "position_depot_storehouse_uuid", "uuid");
	}
}
