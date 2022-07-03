<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotRoleMenu
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $rbac_role_id 角色编号
 * @property int $rbac_menu_id 菜单编号
 * @property-read \App\Model\RbacMenu $menu
 * @property-read \App\Model\RbacRole $role
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu whereRbacMenuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu whereRbacRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleMenu whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PivotRoleMenu extends Model
{
    protected $guarded = [];

    public function role()
    {
        return $this->hasOne(RbacRole::class, 'id', 'rbac_role_id');
    }

    public function menu()
    {
        return $this->hasOne(RbacMenu::class, 'id', 'rbac_menu_id');
    }
}
