<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationWorkshop
 *
 * @package App\Models
 * @property int                           $id
 * @property Carbon                        $created_at
 * @property Carbon                        $updated_at
 * @property Carbon|null                   $deleted_at
 * @property string                        $uuid
 * @property int                           $sort
 * @property string                        $unique_code
 * @property string                        $name
 * @property boolean                       $be_enable
 * @property string                        $organization_workshop_type_uuid
 * @property-read OrganizationWorkshopType $OrganizationWorkshopType
 * @property string                        $organization_paragraph_uuid
 * @property-read OrganizationParagraph    $OrganizationParagraph
 * @property-read OrganizationWorkArea[]   $OrganizationWorkAreas
 * @property-read LocationStation[]        $LocationStations
 * @property-read LocationCenter[]         $LocationCenters
 * @property-read LocationSection[]        $LocationSections
 * @property-read LocationRailroad[]       $LocationRailroads
 * @property-read EntireInstance[]         $EntireInstances
 * @property-read EntireInstanceLog[]      $EntireInstanceLogs
 */
class OrganizationWorkshop extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	public function __toString(): string
	{
		return $this->attributes['name'];
	}
	
	/**
	 * 所属站段
	 *
	 * @return HasOne
	 */
	public function OrganizationParagraph(): HasOne
	{
		return $this->hasOne(OrganizationParagraph::class, "organization_paragraph_uuid", "uuid");
	}
	
	/**
	 * 所属车间类型
	 *
	 * @return HasOne
	 */
	public function OrganizationWorkshopType(): HasOne
	{
		return $this->hasOne(OrganizationWorkshopType::class, "organization_workshop_type_uuid", "uuid");
	}
	
	/**
	 * 相关工区
	 *
	 * @return BelongsTo
	 */
	public function OrganizationWorkAreas(): BelongsTo
	{
		return $this->belongsTo(OrganizationWorkArea::class, "uuid", "organization_workshop_uuid");
	}
	
	/**
	 * 相关站场
	 *
	 * @return BelongsTo
	 */
	public function LocationStations(): BelongsTo
	{
		return $this->belongsTo(LocationStation::class, "organization_workshop_uuid", "uuid");
	}
	
	/**
	 * 相关中心
	 *
	 * @return BelongsTo
	 */
	public function LocationCenters(): BelongsTo
	{
		return $this->belongsTo(LocationCenter::class, "organization_workshop_uuid", "uuid");
	}
	
	/**
	 * 相关区间
	 *
	 * @return BelongsTo
	 */
	public function LocationSections(): BelongsTo
	{
		return $this->belongsTo(LocationSection::class, "organization_workshop_uuid", "uuid");
	}
	
	/**
	 * 相关道口
	 *
	 * @return BelongsTo
	 */
	public function locationrailroads(): BelongsTo
	{
		return $this->belongsTo(LocationRailroad::class, "organization_workshop_uuid", "uuid");
	}
	
	/**
	 * 相关器材
	 *
	 * @return BelongsTo
	 */
	public function EntireInstances(): BelongsTo
	{
		return $this->belongsTo(Entireinstance::class, "belong_to_organization_workshop_uuid", "uuid");
	}
	
	/**
	 * 相关器材日志
	 *
	 * @return BelongsTo
	 */
	public function EntireInstanceLogs(): BelongsTo
	{
		return $this->belongsTo(EntireInstanceLog::class, "organization_workshop_uuid", "uuid");
	}
}
