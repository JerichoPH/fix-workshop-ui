<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductPlanProcess
 *
 * @property-read \App\Model\Account $processor
 * @property-read \App\Model\WarehouseProductPlan $warehouseProductPlan
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPlanProcess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPlanProcess newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPlanProcess onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductPlanProcess query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPlanProcess withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductPlanProcess withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductPlanProcess extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseProductPlan()
    {
        return $this->hasOne(WarehouseProductPlan::class, 'id', 'warehouse_product_plan_id');
    }

    public function processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
