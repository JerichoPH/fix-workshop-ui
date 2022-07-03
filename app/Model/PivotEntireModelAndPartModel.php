<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotEntireModelAndPartModel
 *
 * @property string|null $category_unique_code
 * @property string $entire_model_unique_code
 * @property string $part_model_unique_code
 * @property int $number 整件绑定部件的数量
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \App\Model\PartModel $PartModel
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndPartModel wherePartModelUniqueCode($value)
 * @mixin \Eloquent
 */
class PivotEntireModelAndPartModel extends Model
{
    protected $guarded = [];

    public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    public function PartModel()
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'part_model_unique_code');
    }
}
