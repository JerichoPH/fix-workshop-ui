<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductCount
 *
 * @property-read \App\Model\WarehouseProduct $warehouseProduct
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductCount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductCount newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductCount onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductCount query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductCount withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductCount withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductCount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseProduct()
    {
        return $this->hasOne(WarehouseProduct::class, 'id', 'warehouse_product_id');
    }
}
