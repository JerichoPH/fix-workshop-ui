<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\WarehouseReportProduct
 *
 * @property-read mixed $in_reason
 * @property-read mixed $operation_direction
 * @property-read mixed $out_reason
 * @property-read \App\Model\Account $inPerson
 * @property-read \App\Model\Maintain $maintain
 * @property-read \App\Model\Account $outPerson
 * @property-write mixed $organization_code
 * @property-read \App\Model\WarehouseProductInstance $warehouseProductInstance
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportProduct newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportProduct onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseReportProduct query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportProduct withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\WarehouseReportProduct withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseReportProduct extends Model
{
    use SoftDeletes;

    public static $IN_REASON = [
        'NONE' => '无',
        'BUY_IN' => '采购入库',
        'FIX_BY_SEND' => '返修',
        'FIX_AT_TIME' => '定期维护',
        'FIX_TO_OUT_FINISH' => '出所送检返回',
    ];

    public static $OUT_REASON = [
        'NONE' => '无',
        'INSTALL_OUT' => '安装出所',
        'FIX_BY_SEND_FINISH' => '返修完成',
        'FIX_TO_OUT' => '出所送检',
        'SCRAP' => '报废',
    ];

    public static $OPERATION_DIRECTION = [
        'IN' => '入库',
        'OUT' => '出库'
    ];

    protected $guarded = [];

    public static function flipInReason($value)
    {
        return array_flip(self::$IN_REASON)[$value];
    }

    public static function flipOutReason($value)
    {
        return array_flip(self::$OUT_REASON)[$value];
    }

    public function flipOperationDirection($value)
    {
        return array_flip(self::$OPERATION_DIRECTION)[$value];
    }

    public function getOperationDirectionAttribute($value)
    {
        return self::$OPERATION_DIRECTION[$value];
    }

    public function setOrganizationCodeAttribute($value)
    {
        if (!$value) $this->attributes['organization_code'] = env('ORGANIZATION_CODE');
    }

    public function outPerson()
    {
        return $this->hasOne(Account::class, 'id', 'out_person_id');
    }

    public function inPerson()
    {
        return $this->hasOne(Account::class, 'id', 'in_person_id');
    }

    public function maintain()
    {
        return $this->hasOne(Maintain::class, 'id', 'maintain_id');
    }

    public function warehouseProductInstance()
    {
        return $this->hasOne(WarehouseProductInstance::class, 'open_code', 'warehouse_product_instance_open_code');
    }

    public function getInReasonAttribute($value)
    {
        return self::$IN_REASON[$value];
    }

    public function getOutReasonAttribute($value)
    {
        return self::$OUT_REASON[$value];
    }
}
