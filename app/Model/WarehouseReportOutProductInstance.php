<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReportOutProductInstance
 *
 * @property-read \App\Model\Factory $factory
 * @property-read \App\Model\WarehouseProductInstance $warehouseProductInstance
 * @property-read \App\Model\WarehouseReportOutOrder $warehouseReportOutOrder
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportOutProductInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportOutProductInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportOutProductInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportOutProductInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportOutProductInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportOutProductInstance withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReportOutProductInstance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseReportOutOrder()
    {
        return $this->hasOne(WarehouseReportOutOrder::class, 'serial_number', 'warehouse_report_out_order_serial_number');
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
