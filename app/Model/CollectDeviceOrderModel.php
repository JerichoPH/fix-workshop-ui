<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\CollectDeviceOrderModel
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $category_unique_code 种类代码
 * @property string $entire_model_unique_code 类型代码
 * @property string $sub_model_unique_code 子类代码
 * @property string $part_model_unique_code 型号代码
 * @property string $category_name 种类名称
 * @property string $entire_model_name 类型名称
 * @property string $sub_model_name 子类名称
 * @property string $part_model_name 型号名称
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereEntireModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel wherePartModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel wherePartModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereSubModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereSubModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectDeviceOrderModel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CollectDeviceOrderModel extends Model
{
    protected $guarded = [];
}
