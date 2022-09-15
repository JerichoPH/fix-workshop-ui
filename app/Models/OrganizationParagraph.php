<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationParagraph
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $unique_code
 * @property string $name
 * @property string $short_name
 * @property boolean $be_enable
 * @property string $organization_railway_uuid
 * @property-read OrganizationRailway $organization_railway
 * @property-read OrganizationWorkshop[] $organization_workshops
 * @property-read EntireInstance[] $entire_instances
 * @property-read EntireInstanceLog[] $entire_instance_logs
 */
class OrganizationParagraph extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属路局
     * @return HasOne
     */
    public function OrganizationRailway(): HasOne
    {
        return $this->hasOne(OrganizationRailway::class, "uuid", "organization_railway_uuid");
    }

    /**
     * 相关车间
     * @return BelongsTo
     */
    public function OrganizationWorkshops(): BelongsTo
    {
        return $this->belongsTo(OrganizationWorkshop::class, "uuid", "organization_paragraph_uuid");
    }

    /**
     * 相关器材
     * @return BelongsTo
     */
    public function EntireInstances(): BelongsTo
    {
        return $this->belongsTo(EntireInstance::class, "belong_to_organization_paragraph_uuid", "uuid");
    }

    /**
     * 相关器材日志
     * @return BelongsTo
     */
    public function EntireInstanceLogs(): BelongsTo
    {
        return $this->belongsTo(EntireInstanceLog::class, "organization_paragraph_uuid", "uuid");
    }
}
