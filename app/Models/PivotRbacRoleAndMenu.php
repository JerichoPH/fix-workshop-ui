<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotRbacRoleAndMenu
 * @package App\Models
 * @property int $menu_id
 * @property int $rbac_role_id
 * @property-read Menu $Menu
 * @property-read RbacRole $RbacRole
 */
class PivotRbacRoleAndMenu extends Base
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
     * 相关菜单
     * @return HasOne
     */
    public function Menu(): HasOne
    {
        return $this->hasOne(Menu::class, "id", "menu_id");
    }
}
