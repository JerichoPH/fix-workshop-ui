<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReportOutOrder
 *
 * @property-read mixed $type
 * @property-read \App\Model\Account $processor
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseReportOutProductInstance[] $warehouseReportOutProductInstances
 * @property-read int|null $warehouse_report_out_product_instances_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportOutOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportOutOrder newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportOutOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportOutOrder query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportOutOrder withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportOutOrder withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReportOutOrder extends Model
{
    use SoftDeletes;

    public static $TYPE = [
        'INSTALL' => '安装出所',
        'FIX_TO_OUT' => '出所送检',
        'SCRAP' => '报废'
    ];

    protected $guarded = [];

    public function getTypeAttribute($value)
    {
        return self::$TYPE[$value];
    }

    public function flipType()
    {
        return array_flip(self::$TYPE)[$this->type];
    }

    /**
     * 处理人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    /**
     * 出库设备实例
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function warehouseReportOutProductInstances()
    {
        return $this->hasMany(WarehouseReportOutProductInstance::class, 'warehouse_report_out_order_serial_number', 'serial_number');
    }

}
