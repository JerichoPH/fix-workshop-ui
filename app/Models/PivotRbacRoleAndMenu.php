<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotRbacRoleAndMenu
 * @package App\Models
 * @property int $rbac_role_id
 * @property int $menu_id
 * @property-read RbacRole $rbac_role
 * @property Menu $menu
 */
class PivotRbacRoleAndMenu extends Model
{
    protected $guarded = [];

    /**
     * 相关角色
     * @return HasOne
     */
    public function RbacRole(): HasOne
    {
        return $this->hasOne(RbacRole::class);
    }

    /**
     * 相关菜单
     * @return HasOne
     */
    public function Menu(): HasOne
    {
        return $this->hasOne(Menu::class);
    }
}
