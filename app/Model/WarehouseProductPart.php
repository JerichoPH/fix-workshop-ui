<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductPart
 *
 * @property-read \App\Model\Category $category
 * @property-read mixed $fix_cycle_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseProduct[] $warehouseProducts
 * @property-read int|null $warehouse_products_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPart newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPart onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPart query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPart withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPart withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductPart extends Model
{
    use SoftDeletes;

    public static $FIX_CYCLE_TYPE = [
        'YEAR' => '年',
        'MONTH' => '月',
        'DAY' => '日',
    ];

    protected $guarded = [];

    public function getFixCycleTypeAttribute($value)
    {
        return self::$FIX_CYCLE_TYPE[$value];
    }

    public function flipFixCycleType($value)
    {
        return array_flip(self::$FIX_CYCLE_TYPE)[$value];
    }

    public function warehouseProducts()
    {
        return $this->belongsToMany(
            WarehouseProduct::class,
            'pivot_from_warehouse_product_to_warehouse_product_parts',
            'warehouse_product_part_id',
            'warehouse_product_id'
        );
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'open_code', 'category_open_code');
    }
}
