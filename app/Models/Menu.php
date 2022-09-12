<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Menu
 * @package App\Models
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property string $uuid
 * @property int $sort
 * @property string $name
 * @property string $url
 * @property string $uri_name
 * @property string $parent_uuid
 * @property-read Menu $parent
 * @property-read Menu[] $subs
 * @property string $icon
 */
class Menu extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属父级
     * @return HasOne
     */
    public function Parent(): HasOne
    {
        return $this->hasOne(self::class, "uuid", "parent_uuid");
    }

    /**
     * 相关子集
     * @return BelongsTo
     */
    public function Subs(): BelongsTo
    {
        return $this->belongsTo(self::class, "uuid", "parent_uuid");
    }

    /**
     * 相关角色
     * @return BelongsToMany
     */
    public function RbacRoles(): BelongsToMany
    {
        return $this->belongsToMany(RbacRole::class, "pivot_rbac_role_and_menus", "menu_id", "rbac_role_id");
    }
}
