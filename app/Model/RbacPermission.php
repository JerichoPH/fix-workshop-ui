<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\RbacPermission
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 权限名称
 * @property string $http_path 路径
 * @property int|null $rbac_permission_group_id 权限分组编号
 * @property-read \App\Model\RbacPermissionGroup $permissionGroup
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RbacRole[] $roles
 * @property-read int|null $roles_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacPermission onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereHttpPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereRbacPermissionGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacPermission withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacPermission withoutTrashed()
 * @mixin \Eloquent
 */
class RbacPermission extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(RbacRole::class, 'pivot_role_permissions', 'rbac_role_id', 'rbac_permission_id');
    }

    public function permissionGroup()
    {
        return $this->hasOne(RbacPermissionGroup::class, 'id', 'rbac_permission_group_id');
    }
}
