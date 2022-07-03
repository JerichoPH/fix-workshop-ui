<?php

namespace App\Model;

use Encore\Admin\Auth\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\RbacPermissionGroup
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 名称
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RbacPermission[] $permissions
 * @property-read int|null $permissions_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacPermissionGroup onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacPermissionGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacPermissionGroup withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacPermissionGroup withoutTrashed()
 * @mixin \Eloquent
 */
class RbacPermissionGroup extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function permissions()
    {
        return $this->hasMany(RbacPermission::class,'rbac_permission_group_id','id');
    }
}
