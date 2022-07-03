<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\FixWorkflow
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_instance_identity_code
 * @property string|null $warehouse_report_serial_number
 * @property string $status
 * @property int|null $processor_id
 * @property string|null $expired_at
 * @property int|null $id_by_failed
 * @property string|null $serial_number
 * @property string|null $note
 * @property int $processed_times 检测次数
 * @property string $stage 工单阶段
 * @property string|null $maintain_station_name 台账-站名
 * @property string|null $maintain_location_name 台账-位置代码
 * @property int $is_cycle 是否是周期修检修单
 * @property int $entire_fix_after_count 整件修后检次数统计
 * @property int $part_fix_after_count 部件修后检次数
 * @property string $type 检修单类型
 * @property string|null $check_serial_number 验收的来源检测单
 * @property int $allot_to 计划任务分配到人
 * @property string|null $allot_at 分配日期
 * @property int $is_lock 检修单锁
 * @property-read \App\Model\FixWorkflow $CheckFixWorkflow
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\FixWorkflowProcess[] $FixWorkflowProcesses
 * @property-read int|null $fix_workflow_processes_count
 * @property-read \App\Model\Account $Processor
 * @property-read \App\Model\WarehouseReport $WarehouseReport
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflow onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereAllotAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereAllotTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereCheckSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereEntireFixAfterCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereIdByFailed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereIsCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereIsLock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereMaintainLocationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow wherePartFixAfterCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereProcessedTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflow whereWarehouseReportSerialNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflow withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflow withoutTrashed()
 * @mixin \Eloquent
 */
class FixWorkflow extends Model
{
    use  SoftDeletes;

    public static $TYPE = [
        'FIX' => '检修单',
        'CHECK' => '验收单',
    ];

    public static $STATUS = [
        'UNFIX' => '待处理',
        'IN_WAREHOUSE' => '入库检测',
        'FIX_BEFORE' => '修前检',
        'FIX_AFTER' => '修后检',
        'CHECKED' => '已验收',
        'WORKSHOP' => '车间抽验',
        'SECTION' => '工区验收',
        'FIXED' => '检修完成',
        'RETURN_FACTORY' => '返厂维修',
        'FACTORY_RETURN' => '返厂入所',
        'FIXING' => '检修中',
        'SPOT_CHECK' => '抽验',
        'PROJECT_TEST' => '工程测试',
        'NEW_TEST' => '新设备',
    ];

    public static $STAGE = [
        'UNFIX' => '等待检修',
        'PART' => '部件检测',
        'ENTIRE' => '整件检测',
        'RETURN_FACTORY' => '返厂维修',
        'FACTORY_RETURN' => '返厂回所',
        'FIXED' => '检修完成',
        'CHECKED' => '工区验收',
        'WORKSHOP' => '车间抽验',
        'SECTION' => '段技术科抽验',
        'FIX_BEFORE' => '修前检',
        'FIX_AFTER' => '修后检',
        'WAIT_CHECK' => '等待验收',
        'SPOT_CHECK' => '抽验',
        'PROJECT_TEST' => '工程测试',
        'NEW_TEST' => '新设备',
    ];

    protected $guarded = [];

    public static function flipStatus($value)
    {
        return array_flip(self::$STATUS)[$value];
    }

    public static function flipStage($value)
    {
        return array_flip(self::$STAGE)[$value];
    }

    public static function flipType(string $key)
    {
        return array_flip(self::$TYPE)[$key];
    }

    public function getStageAttribute($value)
    {
        return self::$STAGE[$value];
    }

    public function prototype($attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    public function Processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    public function FixWorkflowProcesses()
    {
        return $this->hasMany(FixWorkflowProcess::class, 'fix_workflow_serial_number', 'serial_number');
    }

    public function WarehouseReport()
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'warehouse_report_serial_number');
    }

    public function getStatusAttribute($value)
    {
        return @self::$STATUS[$value] ?? '';
    }

    public function getTypeAttribute(string $value)
    {
        return @self::$TYPE[$value] ?? '';
    }

    public function CheckFixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class, 'check_serial_number', 'check_serial_number');
    }
}
