<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class RbacPermissionGroup
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $name
 * @property RbacPermission[] $RbacPermissions
 */
class RbacPermissionGroup extends Base
{
    protected $guarded = [];

    /**
     * 相关权限
     * @return HasMany
     */
    public function RbacPermissions(): HasMany
    {
        return $this->hasMany(RbacPermission::class, "rbac_permission_group_uuid", "uuid");
    }
}
