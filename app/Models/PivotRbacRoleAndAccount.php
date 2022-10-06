<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PivotRbacRoleAndAccount
 * @package App\Models
 * @property string $rbac_role_uuid
 * @property-read RbacRole $rbac_role
 * @property string $account_uuid
 * @property-read Account $account
 */
class PivotRbacRoleAndAccount extends Model
{
    protected $guarded = [];

    /**
     * 所属角色
     * @return HasOne
     */
    public function RbacRole(): HasOne
    {
        return $this->hasOne(RbacRole::class,'uuid','rbac_role_uuid');
    }

    /**
     * 所属用户
     * @return HasOne
     */
    public function Account(): HasOne
    {
        return $this->hasOne(Account::class,'uuid','account_uuid');
    }
}
