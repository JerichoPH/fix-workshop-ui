<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\RbacRole
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 名称
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Account[] $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RbacRole[] $menus
 * @property-read int|null $menus_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RbacPermission[] $permissions
 * @property-read int|null $permissions_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacRole onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacRole whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacRole withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacRole withoutTrashed()
 * @mixin \Eloquent
 */
class RbacRole extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function accounts()
    {
        return $this->belongsToMany(Account::class,'pivot_role_accounts','rbac_role_id','account_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(RbacPermission::class,'pivot_role_permissions','rbac_role_id','rbac_permission_id');
    }

    public function menus()
    {
        return $this->belongsToMany(RbacRole::class,'pivot_role_menus','rbac_role_id','rbac_menu_id');
    }
}
