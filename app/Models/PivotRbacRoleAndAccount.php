<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PivotRbacRoleAndAccount
 * @package App\Models
 * @property int $rbac_role_id
 * @property int $account_id
 * @property-read RbacRole $RbacRole
 * @property-read Account $Account
 */
class PivotRbacRoleAndAccount extends Base
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
     * 相关用户
     * @return HasOne
     */
    public function Account(): HasOne
    {
        return $this->hasOne(Account::class, "id", "account_id");
    }
}
