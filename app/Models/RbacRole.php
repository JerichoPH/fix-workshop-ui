<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class RbacRole
 *
 * @package App\Models
 * @property int            $id
 * @property Carbon         $created_at
 * @property Carbon         $updated_at
 * @property Carbon|null    $deleted_at
 * @property string         $uuid
 * @property int            $sort
 * @property string         $name
 * @property-read Account[] $accounts
 * @property-read Menu[]    $menus
 */
class RbacRole extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关用户
	 *
	 * @return BelongsToMany
	 */
	public function Accounts(): BelongsToMany
	{
		return $this->belongsToMany(Account::class, "pivot_rbac_role_and_accounts", "rbac_role_id", "account_id");
	}
	
	/**
	 * 相关菜单
	 *
	 * @return BelongsToMany
	 */
	public function Menus(): BelongsToMany
	{
		return $this->belongsToMany(Menu::class, "pivot_rbac_role_and_menus", "rbac_role_id", "menu_id");
	}
}
