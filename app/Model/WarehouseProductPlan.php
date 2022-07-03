<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductPlan
 *
 * @property-read \App\Model\Account $lastProcessor
 * @property-read \App\Model\WarehouseProductInstance $warehouseProductInstance
 * @property-read \App\Model\WarehouseProductPart $warehouseProductPart
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPlan newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPlan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPlan query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPlan withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPlan withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductPlan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseProductInstance()
    {
        return $this->hasOne(WarehouseProductInstance::class, 'id', 'warehouse_product_instance_id');
    }

    public function warehouseProductPart()
    {
        return $this->hasOne(WarehouseProductPart::class, 'id', 'warehouse_product_part_id');
    }

    public function lastProcessor()
    {
        return $this->hasOne(Account::class, 'id', 'last_processor_id');
    }
}
