<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class RbacPermission
 *
 * @package App\Models
 * @property int                      $id
 * @property Carbon                   $created_at
 * @property Carbon                   $updated_at
 * @property Carbon|null              $deleted_at
 * @property string                   $uuid
 * @property int                      $sort
 * @property string                   $name
 * @property string                   $uri
 * @property string                   $method
 * @property string                   $rbac_permission_group_uuid
 * @property-read RbacPermissionGroup $rbac_permission_group
 */
class RbacPermission extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 所属权限分组
	 *
	 * @return HasOne
	 */
	public function RbacPermissionGroup(): HasOne
	{
		return $this->hasOne(RbacPermissionGroup::class, "uuid", "rbac_permission_group_uuid");
	}
}
