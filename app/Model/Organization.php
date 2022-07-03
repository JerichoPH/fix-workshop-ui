<?php

namespace App\Model;

use App\Facades\OrganizationLevelFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Organization
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 机构名称
 * @property int|null $parent_id 父级编号
 * @property int $level 等级
 * @property int $is_main 是否是主体机构
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Account[] $accounts
 * @property-read int|null $accounts_count
 * @property-read \App\Model\Organization $parent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Organization onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Organization withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Organization withoutTrashed()
 * @mixin \Eloquent
 */
class Organization extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static function getDeepBySession()
    {
        return self::whereIn('id',OrganizationLevelFacade::getDeepBySession())->orderByDesc('id')->get();
    }

    public static function pagniateDeepBySession()
    {
        return self::whereIn('id',OrganizationLevelFacade::getDeepBySession())->orderByDesc('id')->pagniate();
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'organization_id', 'id');
    }

    public function parent()
    {
        return $this->hasOne(Organization::class, 'id', 'parent_id');
    }
}
