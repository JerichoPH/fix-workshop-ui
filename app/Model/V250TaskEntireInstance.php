<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Model\V250TaskEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $v250_task_order_sn 2.5.0版任务单流水号
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property int $fixer_id 检修人
 * @property int $checker_id 验收人
 * @property int $spot_checker_id 抽验人
 * @property string|null $fixed_at 检修时间
 * @property string|null $checked_at 验收时间
 * @property string|null $spot_checked_at 抽验时间
 * @property array $is_scene_back 现场退回设备
 * @property int $is_out 是否已经出所
 * @property string|null $out_at 出所时间
 * @property string $out_warehouse_sn 出所单日期
 * @property int $is_utilize_used 是否是利旧设备
 * @property-read \App\Model\Account $Checker
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\Account $Fixer
 * @property-read \App\Model\Account $SpotChecker
 * @property-read \App\Model\V250TaskOrder $V250TaskOrder
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereCheckerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereFixedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereFixerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereIsOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereIsSceneBack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereIsUtilizeUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereOutWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereSpotCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereSpotCheckerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskEntireInstance whereV250TaskOrderSn($value)
 * @mixin \Eloquent
 */
class V250TaskEntireInstance extends Model
{
    protected $guarded = [];

    /**
     * 相关设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * 获取所属任务单
     */
    final public function V250TaskOrder(): HasOne
    {
        return $this->hasOne(V250TaskOrder::class, 'serial_number', 'v250_task_order_sn');
    }

    /**
     * 检修人
     * @return HasOne
     */
    final public function Fixer(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'fixer_id');
    }

    /**
     * 验收人
     * @return HasOne
     */
    final public function Checker(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'checker_id');
    }

    /**
     * 抽验人
     */
    final public function SpotChecker(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'spot_checker_id');
    }

    /**
     * 是否是现场返回设备标记
     * @param $value
     * @return array
     */
    final public function getIsSceneBackAttribute($value)
    {
        return ['code' => $value, 'name' => $value ? '是' : '否'];
    }
}
