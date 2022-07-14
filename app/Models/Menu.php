<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class Menu
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $uuid
 * @property string $sort
 * @property string $name
 * @property string $url
 * @property string $uri_name
 * @property string $parent_uuid
 * @property-read Menu $Parent
 * @property-read Menu[] $Subs
 * @property string $icon
 * @property RbacRole[] $RbacRoles
 */
class Menu extends Model
{
    protected $guarded = [];

    /**
     * 所属父级
     * @return HasOne
     */
    public function Parent(): HasOne
    {
        return $this->hasOne(Menu::class, "uuid", "parent_uuid");
    }

    /**
     * 相关子集
     * @return HasMany
     */
    public function Subs(): HasMany
    {
        return $this->hasMany(Menu::class, "parent_uuid", "uuid");
    }

    /**
     * 相关角色
     * @return BelongsToMany
     */
    public function RbacRoles(): BelongsToMany
    {
        return $this->belongsToMany("pivot_rbac_role_and_menus", RbacRole::class, "rbac_role_id", "menu_id");
    }
}
