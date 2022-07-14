<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotRbacRoleAndRbacPermission
 * @package App\Models
 * @property int $rbac_permission_model_id
 * @property int $rbac_role_model_id
 * @property string $
 * @property string $
 * @property string $
 */
class PivotRbacRoleAndRbacPermission extends Base
{
    protected $guarded = [];

    /**
     * 相关角色
     * @return HasOne
     */
    public function RbacRole(): HasOne
    {
        return $this->hasOne(RbacRole::class, "id", "rbac_role_id");
    }

    /**
     * 相关权限
     * @return HasOne
     */
    public function RbacPermission(): HasOne
    {
        return $this->hasOne(RbacPermission::class, "id", "rbac_permission_id");
    }
}
