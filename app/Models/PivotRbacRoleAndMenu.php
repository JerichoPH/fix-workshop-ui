<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotRbacRoleAndMenu
 *
 * @package App\Models
 * @property string        $rbac_role_uuid
 * @property-read RbacRole $RbacRole
 * @property string        $menu_uuid
 * @property-read Menu     $Menu
 */
class PivotRbacRoleAndMenu extends Model
{
	protected $guarded = [];
	
	/**
	 * 相关角色
	 *
	 * @return HasOne
	 */
	public function RbacRole(): HasOne
	{
		return $this->hasOne(RbacRole::class, 'uuid', 'rbac_role_uuid');
	}
	
	/**
	 * 相关菜单
	 *
	 * @return HasOne
	 */
	public function Menu(): HasOne
	{
		return $this->hasOne(Menu::class, 'uuid', 'rbac_role_uuid');
	}
}
