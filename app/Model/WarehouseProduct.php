<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProduct
 *
 * @property-read \App\Model\Category $category
 * @property-read mixed $fix_cycle_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Measurement[] $measurements
 * @property-read int|null $measurements_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseProductPart[] $warehouseProductParts
 * @property-read int|null $warehouse_product_parts_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProduct newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProduct onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProduct query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProduct withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProduct withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProduct extends Model
{
    use SoftDeletes;

    public static $FIX_CYCLE_TYPE = [
        'YEAR' => '年',
        'MONTH' => '月',
        'WEEK' => '周',
        'DAY' => '日'
    ];

    protected $guarded = [];

    public function flipFixCycleType()
    {
        return array_flip(self::$FIX_CYCLE_TYPE)[$this->fix_cycle_type];
    }

    public function getFixCycleTypeAttribute($value)
    {
        return self::$FIX_CYCLE_TYPE[$value];
    }

    public function warehouseProductParts()
    {
        return $this->belongsToMany(
            WarehouseProductPart::class,
            'pivot_from_warehouse_product_to_warehouse_product_parts',
            'warehouse_product_id',
            'warehouse_product_part_id'
        );
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_open_code', 'open_code');
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class, 'warehouse_product_id', 'id');
    }
}
