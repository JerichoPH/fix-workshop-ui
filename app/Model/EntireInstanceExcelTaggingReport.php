<?php

namespace App\Model;

use App\Facades\CodeFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\EntireInstanceExcelTaggingReport
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $serial_number 上传流水号
 * @property int $is_upload_create_device_excel_error 是否有设备赋码设备错误报告
 * @property int $is_upload_edit_device_excel_error 是否有批量编辑错误报告
 * @property string $filename 上传文件名
 * @property string $original_filename 原始上传文件名
 * @property string $upload_create_device_excel_error_filename 错误报告文件名
 * @property int $correct_count 成功器材数量
 * @property int $fail_count 失败器材数量
 * @property-read Account $RollbackProcessor 回退执行人
 * @property Carbon $rollback_processed_at 回退执行时间
 * @property-read EntireInstance[] $EntireInstanceIdentityCodes 赋码记录包含器材s
 */
class EntireInstanceExcelTaggingReport extends Model
{
    protected $guarded = [];

    /**
     * 生成序列号
     * @return string
     */
    final static public function generateSerialNumber(): string
    {
        return CodeFacade::makeSerialNumber("TAGGING");

        $today = now()->format("Ymd");
        $last = self::with([])->where("serial_number", "like", session("account.work_area_unique_code") . $today . "%")->orderByDesc("id")->first();
        $next = $last ? intval(substr($last->serial_number, -4)) + 1 : 1;
        return session("account.work_area_unique_code") . $today . str_pad($next, 4, '0', STR_PAD_LEFT);

        // $today = self::with([])->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->orderByDesc('id')->first();
        // $next = $today ? intval(substr($today->serial_number, -4)) + 1 : 1;
        // return session("account.work_area_unique_code") . now()->format('Ymd') . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 赋码唯一编号
     * @return HasMany
     */
    final public function EntireInstanceIdentityCodes(): HasMany
    {
        return $this->hasMany(EntireInstanceExcelTaggingIdentityCode::class, 'entire_instance_excel_tagging_report_sn', 'serial_number');
    }

    /**
     * 操作人
     * @return HasOne
     */
    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    /**
     * 所属工区
     * @return HasOne
     */
    final public function WorkArea(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    /**
     * 所属工区类型
     * @param $value
     * @return object
     */
    final public function getWorkAreaTypeAttribute($value)
    {
        return (object)([
            'code' => $value,
            'name' => array_flip(WorkArea::$WORK_AREA_TYPES)[$value] ?? '无',
        ]);
    }

    final public function RollbackProcessor(): HasOne
    {
        return $this->hasOne(Account::class, "id", "rollback_processor_id");
    }
}
