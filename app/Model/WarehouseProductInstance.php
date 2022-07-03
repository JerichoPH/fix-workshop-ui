<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProductInstance
 *
 * @property-read \App\Model\Factory $factory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\FixWorkflow[] $fixWorkflows
 * @property-read int|null $fix_workflows_count
 * @property-read mixed $status
 * @property-read \App\Model\Maintain $maintain
 * @property-read \App\Model\WarehouseProduct $warehouseProduct
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProductInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProductInstance withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProductInstance extends Model
{
    use SoftDeletes;

    public static $STATUS = [
        'NONE' => '无',
        'BUY_IN' => '采购入库',
        'INSTALLING' => '备品',
        'INSTALLED' => '已安装',
        'FIX_BY_SEND' => '返修入库',
        'FIX_AT_TIME' => '定期维护',
        'FIX_TO_OUT' => '出所送检',
        'FIX_TO_OUT_FINISH' => '出所送检完成',
        'SCRAP' => '报废',
        'FIXED' => '检修完成'
    ];

    protected $guarded = [];

    public static function flipStatus($value)
    {
        return array_flip(self::$STATUS)[$value];
    }

    public function warehouseProduct()
    {
        return $this->hasOne(WarehouseProduct::class, 'unique_code', 'warehouse_product_unique_code');
    }

    public function factory()
    {
        return $this->hasOne(Factory::class, 'unique_code', 'factory_unique_code');
    }

    public function maintain()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_unique_code');
    }

    public function fixWorkflows()
    {
        return $this->hasMany(FixWorkflow::class, 'id', 'fix_workflow_id');
    }

    public function getStatusAttribute($value)
    {
        return self::$STATUS[$value];
    }
}
