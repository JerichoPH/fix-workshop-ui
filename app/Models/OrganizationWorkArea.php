<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationWorkArea
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $unique_code
 * @property string $name
 * @property boolean $be_enable
 * @property string $organization_work_area_type_uuid
 * @property-read OrganizationWorkAreaType $organization_work_area_type
 * @property string $organization_work_area_profession_uuid
 * @property-read OrganizationWorkAreaProfession $organization_work_area_profession
 * @property string $organization_workshop_uuid
 * @property-read OrganizationWorkshop $organization_workshop
 * @property-read LocationStation[] $location_stations
 * @property-read LocationCenter[] $location_centers
 * @property-read LocationSection[] $location_sections
 * @property-read LocationRailroad[] $location_railroades
 * @property-read EntireInstanceLog[] $entire_instance_logs
 */
class OrganizationWorkArea extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属车间
     * @return HasOne
     */
    public function OrganizationWorkshop(): HasOne
    {
        return $this->hasOne(OrganizationWorkShop::class, "uuid", "organization_workshop_uuid");
    }

    /**
     * 所属工区类型
     * @return HasOne
     */
    public function OrganizationWorkAreaType(): HasOne
    {
        return $this->hasOne(OrganizationWorkArea::class, "uuid", "organization_work_area_type_uuid");
    }

    /**
     * 所属工区专业
     * @return HasOne
     */
    public function OrganizationWorkAreaProfession(): HasOne
    {
        return $this->hasOne(OrganizationWorkAreaProfession::class, "uuid", "organization_work_area_profession_uuid");
    }

    /**
     * 相关站场
     * @return BelongsTo
     */
    public function LocationStations(): BelongsTo
    {
        return $this->belongsTo(LocationStation::class, "organization_work_area_uuid", "uuid");
    }

    /**
     * 相关中心
     * @return BelongsTo
     */
    public function LocationCenters(): BelongsTo
    {
        return $this->belongsTo(LocationCenter::class, "organization_work_area_uuid", "uuid");
    }

    /**
     * 相关区间
     * @return BelongsTo
     */
    public function LocationSections(): BelongsTo
    {
        return $this->belongsTo(LocationSection::class, "organization_work_area_uuid", "uuid");
    }

    /**
     * 相关道口
     * @return BelongsTo
     */
    public function locationRailroades(): BelongsTo
    {
        return $this->belongsTo(LocationRailroad::class, "organization_work_area_uuid", "uuid");
    }

    /**
     * 相关器材日志
     * @return BelongsTo
     */
    public function EntireInstanceLogs(): BelongsTo
    {
        return $this->belongsTo(EntireInstanceLog::class, "organization_work_area_uuid", "uuid");
    }
}
