<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\RepairBaseNewStationOrderModel
 *
 * @property-read \App\Model\Category $Category
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \App\Model\RepairBaseNewStationOrder $Order
 * @property-read \App\Model\PartModel $PartModel
 * @property-read \App\Model\EntireModel $SubModel
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrderModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrderModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\RepairBaseNewStationOrderModel query()
 * @mixin \Eloquent
 */
class RepairBaseNewStationOrderModel extends Model
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
        return $this->hasOne(RepairBaseNewStationOrder::class, 'serial_number', 'new_station_model_order_sn');
    }
}
