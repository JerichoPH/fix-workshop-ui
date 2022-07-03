<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotEntireModelAndExtraTag
 *
 * @property string|null $entire_model_unique_code
 * @property string|null $extra_tag
 * @property-read \App\Model\EntireModel $EntireModel
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndExtraTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndExtraTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndExtraTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndExtraTag whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotEntireModelAndExtraTag whereExtraTag($value)
 * @mixin \Eloquent
 */
class PivotEntireModelAndExtraTag extends Model
{
    protected $guarded = [];

    public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }
}
