<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseProcurementPart
 *
 * @property-read \App\Model\Account $processor
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseProcurementPartInstance[] $warehouseProcurementPartInstances
 * @property-read int|null $warehouse_procurement_part_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseReportProductPart[] $warehouseReportProductParts
 * @property-read int|null $warehouse_report_product_parts_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProcurementPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProcurementPart newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProcurementPart onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseProcurementPart query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProcurementPart withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseProcurementPart withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseProcurementPart extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function warehouseProcurementPartInstances()
    {
        return $this->hasMany(WarehouseProcurementPartInstance::class, 'warehouse_procurement_part_id', 'id');
    }

    public function warehouseReportProductParts()
    {
        return $this->hasMany(WarehouseReportProductPart::class, 'warehouse_procurement_part_id', 'id');
    }

    public function processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }
}
