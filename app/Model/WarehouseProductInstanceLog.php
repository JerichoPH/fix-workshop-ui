<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductInstanceLog
 *
 * @property-read \App\Model\WarehouseProductInstance $warehouseProductInstance
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductInstanceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductInstanceLog newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductInstanceLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductInstanceLog query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductInstanceLog withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductInstanceLog withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductInstanceLog extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseProductInstance()
    {
        return $this->hasOne(WarehouseProductInstance::class, 'open_code', 'warehouse_product_instance_open_code');
    }
}
