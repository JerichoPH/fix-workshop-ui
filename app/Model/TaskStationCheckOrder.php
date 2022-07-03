<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * App\Model\TaskStationCheckOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $serial_number 任务单号
 * @property string $work_area_unique_code 工区代码
 * @property string $maintain_station_unique_code 车站代码
 * @property string $scene_workshop_unique_code 现场车间代码
 * @property int $principal_id_level_1 1级负责人（科长）
 * @property int $principal_id_level_2 2级负责人（主管工程师）
 * @property int $principal_id_level_3 3级负责人（现场车间主任）
 * @property int $principal_id_level_4 4级负责人（现场工区工长）
 * @property int $principal_id_level_5 5级负责人（现场工区员工）
 * @property string|null $expiring_at 截止日期
 * @property string|null $finished_at 实际完成时间
 * @property string $title 项目标题
 * @property string $project 项目名称
 * @property string $unit 单位
 * @property int $number 任务数量
 * @property-read \App\Model\Maintain $MaintainStation
 * @property-read \App\Model\Account $PrincipalIdLevel1
 * @property-read \App\Model\Account $PrincipalIdLevel2
 * @property-read \App\Model\Account $PrincipalIdLevel3
 * @property-read \App\Model\Account $PrincipalIdLevel4
 * @property-read \App\Model\Account $PrincipalIdLevel5
 * @property-read \App\Model\Maintain $SceneWorkshop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\TaskStationCheckEntireInstance[] $TaskStationCheckEntireInstances
 * @property-read int|null $task_station_check_entire_instances_count
 * @property-read \App\Model\WorkArea $WorkArea
 * @property-read array $status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereExpiringAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder wherePrincipalIdLevel1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder wherePrincipalIdLevel2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder wherePrincipalIdLevel3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder wherePrincipalIdLevel4($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder wherePrincipalIdLevel5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereProject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereSceneWorkshopUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\TaskStationCheckOrder whereWorkAreaUniqueCode($value)
 * @mixin \Eloquent
 */
class TaskStationCheckOrder extends Model
{
    public static $STATUSES = ['UNDONE' => '未完成', 'DONE' => '已完成',];
    protected $guarded = [];

    /**
     * 生成新任务单号
     * @param string $scene_workshop_unique_code
     * @return string
     */
    final public static function generateSerialNumber(string $scene_workshop_unique_code): string
    {
        $last_order = TaskStationCheckOrder::with([])->orderByDesc('id')->first();
        if ($last_order) {
            $max_sn = intval(Str::substr($last_order->serial_number, -4));
        } else {
            $max_sn = 0;
        }
        $new_sn = str_pad($max_sn + 1, 4, '0', 0);
        return $scene_workshop_unique_code . date('Ymd') . $new_sn;
    }

    /**
     * 车站
     * @return HasOne
     */
    final public function MaintainStation(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }

    /**
     * 现场车间
     * @return HasOne
     */
    final public function SceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'scene_workshop_unique_code');
    }

    /**
     * 工区
     * @return HasOne
     */
    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    /**
     * 1级负责人（科长）
     * @return HasOne
     */
    final public function PrincipalIdLevel1(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'principal_id_level_1');
    }

    /**
     * 2级负责人（主管工程师）
     * @return HasOne
     */
    final public function PrincipalIdLevel2(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'principal_id_level_2');
    }

    /**
     * 3级负责人（现场车间主任）
     * @return HasOne
     */
    final public function PrincipalIdLevel3(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'principal_id_level_3');
    }

    /**
     * 4级负责人（现场工区工长）
     * @return HasOne
     */
    final public function PrincipalIdLevel4(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'principal_id_level_4');
    }

    /**
     * 5级负责人（现场工区职工）
     * @return HasOne
     */
    final public function PrincipalIdLevel5(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'principal_id_level_5');
    }

    /**
     * 现场检修设备
     */
    final public function TaskStationCheckEntireInstances(): HasMany
    {
        return $this->hasMany(TaskStationCheckEntireInstance::class, 'task_station_check_order_sn', 'serial_number');
    }

    /**
     * 获取状态
     * @param $value
     * @return array
     */
    final public function getStatusAttribute($value): array
    {
        return ['code' => $value, 'value' => self::$STATUSES[$value] ?? '无'];
    }

    /**
     * 检修计划
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function WithCheckPlan()
    {
        return $this->belongsTo(CheckPlan::class, 'check_plan_serial_number', 'serial_number');
    }
}
