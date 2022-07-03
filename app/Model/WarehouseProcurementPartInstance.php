<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProcurementPartInstance
 *
 * @property-read \App\Model\WarehouseProductPart $warehouseProcurementPart
 * @property-read \App\Model\WarehouseProductPart $warehouseProductPart
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProcurementPartInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProcurementPartInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProcurementPartInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProcurementPartInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProcurementPartInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProcurementPartInstance withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProcurementPartInstance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseProcurementPart()
    {
        return $this->belongsTo(WarehouseProductPart::class,'id','warehouse_procurement_part_id');
    }

    public function warehouseProductPart()
    {
        return $this->hasOne(WarehouseProductPart::class,'id','warehouse_product_part_id');
    }
}
