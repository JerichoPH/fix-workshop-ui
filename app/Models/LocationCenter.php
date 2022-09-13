<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class LocationCenter
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
 * @property string $organization_workshop_uuid
 * @property-read OrganizationWorkshop $organization_workshop
 * @property string $organization_work_area_uuid
 * @property-read OrganizationWorkArea $organization_work_area
 * @property-read LocationLine[] $location_lines
 */
class LocationCenter extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属车间
     * @return HasOne
     */
    public function OrganizationWorkshop(): HasOne
    {
        return $this->hasOne(OrganizationWorkshop::class, "uuid", "organization_workshop_uuid");
    }

    /**
     * 所属工区
     * @return HasOne
     */
    public function OrganizationWorkArea(): HasOne
    {
        return $this->hasOne(OrganizationWorkshop::class, "uuid", "organization_work_area_uuid");
    }

    /**
     * 相关线别
     * @return BelongsToMany
     */
    public function LocationLines(): BelongsToMany
    {
        return $this->belongsToMany(LocationLine::class, "pivot_location_line_and_location_centers", "location_center_id", "location_line_id");
    }
}
