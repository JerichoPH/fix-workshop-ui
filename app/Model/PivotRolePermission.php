<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotRolePermission
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $rbac_role_id 关联角色
 * @property int $rbac_permission_id 关联权限
 * @property-read \App\Model\RbacPermission $permission
 * @property-read \App\Model\RbacRole $role
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission whereRbacPermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission whereRbacRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRolePermission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PivotRolePermission extends Model
{
    protected $guarded = [];

    public function role()
    {
        return $this->hasOne(RbacRole::class, 'id', 'rbac_role_id');
    }

    public function permission()
    {
        return $this->hasOne(RbacPermission::class, 'id', 'rbac_permission_id');
    }
}
