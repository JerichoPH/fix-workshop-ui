<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotRbacRoleAndAccount
 * @package App\Models
 * @property string $rbac_role_uuid
 * @property-read RbacRole $rbac_role
 * @property string $rbac_permission_uuid
 * @property-read RbacPermission $rbac_permission
 */
class PivotRbacRoleAndRbacPermission extends Model
{
    protected $guarded = [];

    /**
     * 所属角色
     * @return HasOne
     */
    public function RbacRole(): HasOne
    {
        return $this->hasOne(RbacRole::class, 'uuid', 'rbac_role_uuid');
    }

    /**
     * 所属权限
     * @return HasOne
     */
    public function RbacPermission(): HasOne
    {
        return $this->hasOne(RbacPermission::class, 'uuid', 'rbac_permission_uuid');
    }
}
