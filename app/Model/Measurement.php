<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\Measurement
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $identity_code 检修数据模板识别码
 * @property string $entire_model_unique_code 整件型号代码
 * @property string|null $part_model_unique_code 部件型号代码
 * @property string|null $key 测试项名称
 * @property float|null $allow_min 允许最小值
 * @property float|null $allow_max 允许最大值
 * @property string|null $allow_explain 允许描述
 * @property string|null $unit 单位
 * @property string|null $operation 操作
 * @property string|null $explain 说明
 * @property string|null $character 测试特性
 * @property int $is_extra_tag 是否是额外测试标签
 * @property string|null $extra_tag 额外测试项标签
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \App\Model\PartModel $PartModel
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Measurement onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereAllowExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereAllowMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereAllowMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereCharacter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereExtraTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereIsExtraTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereOperation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement wherePartModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\Measurement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Measurement withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\Measurement withoutTrashed()
 * @mixin \Eloquent
 */
class Measurement extends Model
{
    use SoftDeletes;

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
