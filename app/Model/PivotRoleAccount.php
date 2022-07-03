<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotRoleAccount
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $rbac_role_id 绑定角色
 * @property int $account_id 绑定用户
 * @property-read \App\Model\Account $accounts
 * @property-read \App\Model\RbacRole $roles
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount whereRbacRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotRoleAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PivotRoleAccount extends Model
{
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsTo(RbacRole::class, 'rbac_role_id', 'id');
    }

    public function accounts()
    {
        return $this->belongsTo(Account::class,'account_id','id');
    }
}
