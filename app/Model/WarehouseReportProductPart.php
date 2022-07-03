<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReportProductPart
 *
 * @property-read \App\Model\FixWorkflow $fixWorkflow
 * @property-read mixed $operation_direction
 * @property-read \App\Model\Account $inPerson
 * @property-read \App\Model\WarehouseProcurementPart $warehouseProcurementPart
 * @property-read \App\Model\WarehouseProductPart $warehouseProductPart
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportProductPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportProductPart newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportProductPart onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportProductPart query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportProductPart withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportProductPart withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReportProductPart extends Model
{
    use SoftDeletes;

    public static $OPERATION_DIRECTION = [
        'IN' => '入库',
        'OUT' => '出库'
    ];

    protected $guarded = [];

    public function getOperationDirectionAttribute($value)
    {
        return self::$OPERATION_DIRECTION[$value];
    }

    public function flipOperationDirection($value)
    {
        return array_flip(self::$OPERATION_DIRECTION)[$value];
    }

    public function warehouseProcurementPart()
    {
        return $this->hasOne(WarehouseProcurementPart::class, 'id', 'warehouse_procurement_part_id');
    }

    public function warehouseProductPart()
    {
        return $this->hasOne(WarehouseProductPart::class, 'id', 'warehouse_product_part_id');
    }

    public function inPerson()
    {
        return $this->hasOne(Account::class, 'id', 'in_person_id');
    }

    public function fixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class, 'id', 'fix_workflow_id');
    }
}
