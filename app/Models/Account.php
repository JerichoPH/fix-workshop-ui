<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class Account
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $username
 * @property string $password
 * @property string $nickname
 * @property-read RbacRole[] $RbacRoles
 */
class Account extends Base
{
    protected $guarded = [];

    /**
     * 相关角色
     */
    public function RbacRoles(): BelongsToMany
    {
        return $this->belongsToMany("pivot_rbac_role_and_accounts", RbacRole::class, "rbac_role_id", "account_id");
    }
}
