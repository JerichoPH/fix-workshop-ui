<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\PivotFromWarehouseProductToWarehouseProductPart
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $warehouse_product_id 成品编号
 * @property int $warehouse_product_part_id 零件编号
 * @property int $number
 * @property-read \App\Model\WarehouseProduct $warehouseProduct
 * @property-read \App\Model\WarehouseProductPart $warehouseProductPart
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart whereWarehouseProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\PivotFromWarehouseProductToWarehouseProductPart whereWarehouseProductPartId($value)
 * @mixin \Eloquent
 */
class PivotFromWarehouseProductToWarehouseProductPart extends Model
{
    protected $guarded = [];

    public function warehouseProduct()
    {
        return $this->hasOne(WarehouseProduct::class, 'id', 'warehouse_product_id');
    }

    public function warehouseProductPart()
    {
        return $this->hasOne(WarehouseProductPart::class, 'id', 'warehouse_product_part_id');
    }
}
