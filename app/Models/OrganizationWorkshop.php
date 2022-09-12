<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class OrganizationWorkshop
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
 * @property string $organization_workshop_type_uuid
 * @property-read OrganizationWorkshopType $organization_workshop_type
 * @property string $organization_paragraph_uuid
 * @property-read OrganizationParagraph $organization_paragraph
 * @property-read OrganizationWorkArea[] $organization_work_areas
 */
class OrganizationWorkshop extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属站段
     * @return HasOne
     */
    public function OrganizationParagraph(): HasOne
    {
        return $this->hasOne(OrganizationParagraph::class, "organization_paragraph_uuid", "uuid");
    }

    /**
     * 所属车间类型
     * @return HasOne
     */
    public function OrganizationWorkshopType(): HasOne
    {
        return $this->hasOne(OrganizationWorkshopType::class, "organization_workshop_type_uuid", "uuid");
    }

    /**
     * 相关工区
     * @return BelongsTo
     */
    public function OrganizationWorkAreas(): BelongsTo
    {
        return $this->belongsTo(OrganizationWorkArea::class, "uuid", "organization_workshop_uuid");
    }
}
