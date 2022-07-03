<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\PartCategory
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $name 部件种类名称
 * @property string $category_unique_code 所属种类代码
 * @property int $is_main 是否是主要部件
 * @property string $entire_model_unique_code 类型代码
 * @property-read \App\Model\Category $Category
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartInstance[] $PartInstances
 * @property-read int|null $part_instances_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PartCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartCategory withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\PartCategory withoutTrashed()
 * @mixin \Eloquent
 */
class PartCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 所属种类
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Category()
    {
        return $this->hasOne(Category::class, "unique_code", "category_unique_code");
    }

    /**
     * 所属器材类型
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function EntireModel()
    {
        return $this->hasOne(EntireModel::class,'unique_code','entire_model_unique_code');
    }

    /**
     * 部件实例
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    final public function PartInstances()
    {
        return $this->hasMany(PartInstance::class, 'part_category_id','id');
    }
}
