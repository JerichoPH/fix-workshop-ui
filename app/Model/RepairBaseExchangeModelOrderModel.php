<?php

namespace App\Model;

use App\Http\Controllers\RepairBase\ExchangeModelOrderController;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseExchangeModelOrderModel
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $model_name 型号名称
 * @property string $model_unique_code 型号代码
 * @property string $entire_model_name 类型名称
 * @property string $entire_model_unique_code 类型代码
 * @property string $category_name 种类名称
 * @property string $category_unique_code 种类代码
 * @property int $work_area_id 所属工区
 * @property int $number 所需数量
 * @property string $exchange_model_order_sn 更换型号计划单
 * @property int $picked 是否选中
 * @property-read \App\Model\Category $Category
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \App\Model\RepairBaseExchangeModelOrder $Order
 * @property-read \App\Model\PartModel $PartModel
 * @property-read \App\Model\EntireModel $SubModel
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereEntireModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereExchangeModelOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel wherePicked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseExchangeModelOrderModel whereWorkAreaId($value)
 * @mixin \Eloquent
 */
class RepairBaseExchangeModelOrderModel extends Model
{
    protected $guarded = [];

    final public function SubModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'model_unique_code');
    }

    final public function PartModel()
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'model_unique_code');
    }

    final public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    final public function Category()
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    final public function Order()
    {
        return $this->hasOne(RepairBaseExchangeModelOrder::class, 'serial_number', 'exchange_model_order_sn');
    }
}
