<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReportInProductInstance
 *
 * @property-read \App\Model\Factory $factory
 * @property-read \App\Model\WarehouseProductInstance $warehouseProductInstance
 * @property-read \App\Model\WarehouseReportInOrder $warehouseReportInOrder
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportInProductInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportInProductInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportInProductInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportInProductInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportInProductInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportInProductInstance withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReportInProductInstance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseReportInOrder()
    {
        return $this->hasOne(WarehouseReportInOrder::class, 'serial_number', 'warehouse_report_in_order_serial_number');
    }

    public function warehouseProductInstance()
    {
        return $this->hasOne(WarehouseProductInstance::class,'open_code','warehouse_product_instance_open_code');
    }

    public function factory()
    {
        return $this->hasOne(Factory::class,'unique_code','factory_unique_code');
    }
}
