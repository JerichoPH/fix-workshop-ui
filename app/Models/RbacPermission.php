<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class RbacPermission
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $name
 * @property string $uri
 * @property string $method
 * @property string $rbac_permission_group_uuid
 * @property-read RbacPermissionGroup $RbacPermissionGroup
 */
class RbacPermission extends Base
{
    protected $guarded = [];

    /**
     * 相关角色
     * @return BelongsToMany
     */
    public function RbacRoles(): BelongsToMany
    {
        return $this->belongsToMany("pivot_rbac_role_and_rbac_permissions", RbacRole::class, "rbac_role_id", "rbac_permission_id");
    }

    /**
     * 所属权限分组
     * @return HasOne
     */
    public function RbacPermissionGroup(): HasOne
    {
        return $this->hasOne(RbacPermissionGroup::class, "uuid", "rbac_permission_group_uuid");
    }
}
