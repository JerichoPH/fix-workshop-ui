<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\EntireInstanceCount
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_model_unique_code 整件型号代码
 * @property int $count 累计数
 * @property int|null $year 设备入所年份
 * @property-read \App\Model\EntireModel $EntireModel
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceCount onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceCount whereYear($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceCount withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstanceCount withoutTrashed()
 * @mixin \Eloquent
 */
class EntireInstanceCount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * 批量更新
     * @param array $data
     * @throws \Throwable
     */
    final public static function updates(array $data)
    {
        foreach ($data as $entire_model_unique_code => $count) {
            $eic = EntireInstanceCount::with([])->where('entire_model_unique_code', $entire_model_unique_code)->first();
            if ($eic) {
                $eic->fill(['entire_model_unique_code' => $entire_model_unique_code, 'count' => $count,])->saveOrFail();
            } else {
                EntireInstanceCount::with([])->create(['entire_model_unique_code' => $entire_model_unique_code, 'count' => $count,]);
            }
        }
    }

    public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }
}
