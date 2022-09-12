<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Account
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $username
 * @property string $password
 * @property string $nickname
 * @property string $organization_railway_uuid
 * @property-read OrganizationRailway|null $organization_railway
 * @property string $organization_paragraph_uuid
 * @property-read OrganizationParagraph $organization_paragraph
 * @property string $organization_workshop_uuid
 * @property-read OrganizationWorkshop $organization_workshop
 * @property string $organization_work_area_uuid
 * @property-read OrganizationWorkArea $organization_work_area
 */
class Account extends Model
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
     * 所属站段
     * @return HasOne
     */
    public function OrganizationParagraph(): HasOne
    {
        return $this->hasOne(OrganizationParagraph::class, "uuid", "organization_paragraph_uuid");
    }

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
        return $this->hasOne(OrganizationWorkArea::class, "uuid", "organization_work_area_uuid");
    }

    /**
     * 相关角色
     * @return BelongsToMany
     */
    public function RbacRoles(): BelongsToMany
    {
        return $this->belongsToMany(RbacRole::class, "pivot_rbac_role_and_accounts", "account_id", "rbac_role_id");
    }
}
