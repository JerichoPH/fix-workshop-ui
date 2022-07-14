<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class RbacRole
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $name
 * @property-read Account[] $Accounts
 * @property-read RbacPermission[] $RbacPermissions
 */
class RbacRole extends Base
{
    protected $guarded = [];

    /**
     * 相关用户
     * @return BelongsToMany
     */
    public function Accounts(): BelongsToMany
    {
        return $this->belongsToMany("pivot_rbac_role_and_accounts", Account::class, "account_id", "rbac_role_id");
    }

    /**
     * 相关权限
     * @return BelongsToMany
     */
    public function Permissions(): BelongsToMany
    {
        return $this->belongsToMany("pivot_rbac_role_and_rbac_permissions", RbacPermission::class, "rbac_permission_id", "rbac_role_id");
    }
}
