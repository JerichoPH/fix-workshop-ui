<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\TempTaskSubOrderEntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $old_entire_instance_identity_code 待替换设备编号
 * @property string $new_entire_instance_identity_code 待上道设备编号
 * @property int $is_finished 是否已经完成
 * @property string $in_warehouse_sn 入所单号
 * @property string $out_warehouse_sn 出所单号
 * @property string $fix_workflow_sn 检修单号
 * @property int|null $fixer_id 检修人
 * @property string $fixer_nickname 检修人昵称
 * @property int|null $checker_id 验收人
 * @property string $checker_nickname 验收人姓名
 * @property int $temp_task_id 临时生产任务ID
 * @property int $temp_task_sub_order_id 临时生产子任务ID
 * @property string $model_unique_code
 * @property string $model_name
 * @property int $in_scan 入所扫码
 * @property int $out_scan 出所扫码
 * @property string $maintain_location_code 组合位置
 * @property string $crossroad_number 道岔号
 * @property string $open_direction 开向
 * @property string $temp_task_type 任务类型：
 * NEW_STATION：新站
 * FULL_FIX：大修
 * STATION_REMOULD：站改
 * TECHNOLOGY_REMOULD：技改
 * HIGH_FREQUENCY：高频/状态
 * @property-read \App\Model\Account $Checker
 * @property-read \App\Model\FixWorkflow $FixWorkflow
 * @property-read \App\Model\Account $Fixer
 * @property-read \App\Model\Warehouse $InWarehouse
 * @property-read \App\Model\Account $MissionFixer
 * @property-read \App\Model\EntireInstance $NewEntireInstance
 * @property-read \App\Model\EntireInstance $OldEntireInstance
 * @property-read \App\Model\Warehouse $OutWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereCheckerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereCheckerNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereFixWorkflowSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereFixerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereFixerNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereInScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereInWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereIsFinished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereNewEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereOldEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereOutScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereOutWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereTempTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereTempTaskSubOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereTempTaskType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TempTaskSubOrderEntireInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TempTaskSubOrderEntireInstance extends Model
{
    protected $guarded = [];

    /**
     * 旧设备
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function OldEntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'old_entire_instance_identity_code');
    }

    /**
     * 新设备
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function NewEntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'new_entire_instance_identity_code');
    }

    /**
     * 检修人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Fixer()
    {
        return $this->hasOne(Account::class, 'id', 'fixer_id');
    }

    /**
     * 验收人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Checker()
    {
        return $this->hasOne(Account::class, 'id', 'checker_id');
    }

    /**
     * 检修单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function FixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class, 'serial_number', 'fix_workflow_sn');
    }

    /**
     * 入所单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function InWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'serial_number', 'in_warehouse_sn');
    }

    /**
     * 出所单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function OutWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'serial_number', 'out_warehouse_sn');
    }

    /**
     * 检修任务人
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function MissionFixer()
    {
        return $this->hasOne(Account::class, 'id', 'mission_fixer_id');
    }
}
