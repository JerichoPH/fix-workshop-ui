<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Factory
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 厂家名称
 * @property string|null $phone 联系电话
 * @property string|null $official_home_link 官网地址
 * @property string|null $unique_code 统一代码
 * @property string|null $short_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstance[] $EntireInstances
 * @property-read int|null $entire_instances_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Factory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereOfficialHomeLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Factory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Factory withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Factory withoutTrashed()
 * @mixin \Eloquent
 */
class Factory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function EntireInstances()
    {
        return $this->hasMany(EntireInstance::class, 'name', 'factory_name');
    }
}
