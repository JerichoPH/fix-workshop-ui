<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * App\Model\V250TaskOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $scene_workshop_unique_code 现场车间代码
 * @property string $maintain_station_unique_code 车站代码
 * @property string $serial_number 流水号
 * @property string|null $expiring_at 截止日期
 * @property string|null $finished_at 完成时间
 * @property int $principal_id 负责人编号
 * @property string $work_area_unique_code 所属工区
 * @property array $status undone：未完成
 * processing：处理中
 * done：已完成
 * cancel：取消
 * @property array $type NIL：无
 * NEW_STATION：新站
 * RECYCLE：回收
 * CYCLE_FIX：周期修
 * UNCYCLE_FIX：状态修
 * BREAKDONW：故障修
 * @property int $is_upload_create_device_excel_error 设备赋码上传Excel错误标记
 * @property int $is_upload_install_location_excel_error 上传上道位置excel错误
 * @property int $is_upload_check_device_excel_error 上传验收设备excel错误信息
 * @property string|null $delivery_message 交付总结
 * @property int $is_upload_edit_device_excel_error 上传设备数据补充Excel错误
 * @property-read \App\Model\Maintain $MaintainStation
 * @property-read \App\Model\Account $Principal
 * @property-read \App\Model\Maintain $SceneWorkshop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\V250TaskEntireInstance[] $V250TaskEntireInstances
 * @property-read int|null $v250_task_entire_instances_count
 * @property-read \App\Model\WorkArea $WorkAreaByUniqueCode
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereDeliveryMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereExpiringAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereIsUploadCheckDeviceExcelError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereIsUploadCreateDeviceExcelError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereIsUploadEditDeviceExcelError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereIsUploadInstallLocationExcelError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereMaintainStationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder wherePrincipalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereSceneWorkshopUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\V250TaskOrder whereWorkAreaUniqueCode($value)
 * @mixin \Eloquent
 */
class V250TaskOrder extends Model
{
    public static $STATUSES = [
        'UNDONE' => '未完成',
        'PROCESSING' => '处理中',
        'DONE' => '已处理',
        'CANCEL' => '已取消',
    ];
    public static $TYPES = [
        'NIL' => '无',
        'NEW_STATION' => '新开站',
        'RECYCLE' => '回收',
        'CYCLE_FIX' => '周期修',
        'UNCYCLE_FIX' => '状态修',
        'BREAKDOWN' => '故障修',
        'CHANGE_MODEL' => '换型',
    ];
    public static $TYPE_TO_SERIAL_NUMBERS = [
        'NIL' => '00',
        'NEW_STATION' => '01',
        'RECYCLE' => '02',
        'CYCLE_FIX' => '03',
        'UNCYCLE_FIX' => '04',
        'BREAKDOWN' => '05',
        'CHANGE_MODEL' => '06',
    ];
    protected $guarded = [];

    /**
     * 获取新流水号
     * @param string $type
     * @return string
     */
    final public static function getNewSN(string $type = 'NIL'): string
    {
        $todayOrigin = date('Y-m-d 00:00:00');
        $todayFinish = date('Y-m-d 23:59:59');

        $max = self::with([])
            ->whereBetween('created_at', [$todayOrigin, $todayFinish])
            ->orderByDesc('id')
            ->first();
        if (!$max) {
            $next = 1;
        } else {
            $next = intval(Str::substr($max->serial_number, -2));
        }
        return env('ORGANIZATION_CODE')
            . (self::$TYPE_TO_SERIAL_NUMBERS[$type] ?? '00')
            . date('Ymd')
            . str_pad(++$next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 获取类型
     * @param $value
     * @return array
     */
    final public function getTypeAttribute($value): array
    {
        return ['code' => $value, 'name' => self::$TYPES[$value]];
    }

    /**
     * 获取状态
     * @param $value
     * @return array
     */
    final public function getStatusAttribute($value): array
    {
        return ['code' => $value, 'name' => self::$STATUSES[$value]];
    }

    /**
     * 获取任务单下设备
     * @return HasMany
     */
    final public function V250TaskEntireInstances(): HasMany
    {
        return $this->hasMany(V250TaskEntireInstance::class, 'v250_task_order_sn', 'serial_number');
    }

    /**
     * 负责人
     * @return HasOne
     */
    final public function Principal(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'principal_id');
    }

    /**
     * 所属工区
     * @return HasOne
     */
    final public function WorkAreaByUniqueCode(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
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
     * 车站
     * @return HasOne
     */
    final public function MaintainStation(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }


}
