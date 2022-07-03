<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\EntireModelIdCode
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $category_unique_code 种类代码
 * @property string $entire_model_unique_code 类型代码
 * @property string|null $code 型号代码
 * @property string|null $name 型号名称
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstance[] $EntireInstance
 * @property-read int|null $entire_instance_count
 * @property-read \App\Model\EntireModel $EntireModel
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireModelIdCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EntireModelIdCode extends Model
{
    protected $guarded = [];

    public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    public function EntireInstance()
    {
        return $this->hasMany(EntireInstance::class, 'entire_model_id_code', 'code');
    }
}
