<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\OverhaulEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $v250_task_order_sn 2.5.0版任务单流水号
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property int|null $fixer_id 检修人
 * @property string|null $fixed_at 检修时间
 * @property int|null $checker_id 验收人
 * @property string|null $checked_at 验收时间
 * @property int|null $spot_checker_id 抽验人
 * @property string|null $spot_checked_at 抽验时间
 * @property string $allocate_at 分配时间
 * @property string $deadline 截至时间
 * @property string $status 0:未完成,1:已完成,2:超期完成
 * @property-read \App\Model\Account $Checker
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\EntireInstance $EntireInstanceIdentityCode
 * @property-read \App\Model\Account $Fixer
 * @property-read \App\Model\Account $SpotChecker
 * @property-read \App\Model\V250TaskOrder $V250TaskOrder
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereAllocateAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereCheckerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereFixedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereFixerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereSpotCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereSpotCheckerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OverhaulEntireInstance whereV250TaskOrderSn($value)
 * @mixin \Eloquent
 */
class OverhaulEntireInstance extends Model
{
    public static $STATUSES = [
        '0' => '未完成',
        '1' => '已完成',
        '2' => '超期完成',
    ];
    protected $guarded = [];

    final public function V250TaskOrder(): HasOne
    {
        return $this->hasOne(V250TaskOrder::class, 'serial_number', 'v250_task_order_sn');
    }

    final public function EntireInstanceIdentityCode(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    final public function Fixer(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'fixer_id');
    }

    final public function Checker(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'checker_id');
    }

    final public function SpotChecker()
    {
        return $this->hasOne(Account::class, 'id', 'spot_checker_id');
    }

    public function getStatusAttribute($value)
    {
        return ['code' => $value, 'name' => self::$STATUSES[$value] ?? '无'];
    }

    /**
     * 相关设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
