<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class RbacPermissionGroup
 *
 * @package App\Models
 * @property int                 $id
 * @property Carbon              $created_at
 * @property Carbon              $updated_at
 * @property Carbon|null         $deleted_at
 * @property string              $uuid
 * @property int                 $sort
 * @property string              $name
 * @property-read RbacPermission $rbac_permissions
 */
class RbacPermissionGroup extends Model
{
	use SoftDeletes;
	
	protected $guarded = [];
	
	/**
	 * 相关权限
	 *
	 * @return BelongsTo
	 */
	public function RbacPermissions(): BelongsTo
	{
		return $this->belongsTo(RbacPermission::class, "rbac_permission_group_uuid", "uuid");
	}
}
