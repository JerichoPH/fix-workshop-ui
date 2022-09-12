<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PivotRbacRoleAndRbacPermission extends Model
{
    protected $guarded = [];

    /**
     * 所属角色
     * @return HasOne
     */
    public function RbacRole(): HasOne
    {
        return $this->hasOne(RbacRole::class);
    }

    /**
     * 所属权限
     * @return HasOne
     */
    public function RbacPermission(): HasOne
    {
        return $this->hasOne(RbacPermission::class);
    }
}
