<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\RbacMenu
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $title 菜单名称
 * @property int|null $parent_id 父级菜单编号
 * @property int|null $sort 排序依据
 * @property string|null $icon 图表名称
 * @property string|null $uri 统一资源标识
 * @property string|null $sub_title 副标题
 * @property string|null $action_as 菜单指向路由别名
 * @property-read \App\Model\RbacMenu $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\RbacRole[] $roles
 * @property-read int|null $roles_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacMenu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereActionAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereSubTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RbacMenu whereUri($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacMenu withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\RbacMenu withoutTrashed()
 * @mixin \Eloquent
 */
class RbacMenu extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(RbacRole::class, 'pivot_role_menus', 'rbac_menu_id', 'rbac_role_id');
    }

    public function parent()
    {
        return $this->hasOne(RbacMenu::class, 'id', 'parent_id');
    }
}
