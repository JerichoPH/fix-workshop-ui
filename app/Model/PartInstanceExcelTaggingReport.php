<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PartInstanceExcelTaggingReport
 * @package App\Model
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $serial_number
 * @property boolean $is_upload_create_device_excel_error
 * @property boolean $is_upload_edit_device_excel_error
 * @property string $work_area_type
 * @property int $processor_id
 * @property string $work_area_unique_code
 * @property-read PartInstance[] $PartInstanceIdentityCodes
 * @property-read Account $Processor
 * @property-read WorkArea $WorkArea
 */
class PartInstanceExcelTaggingReport extends Model
{
    protected $guarded = [];

    /**
     * 生成序列号
     * @return string
     */
    final static public function generateSerialNumber(): string
    {
        $today = self::with([])->orderByDesc('created_at')->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->first();
        $next = $today ? intval(substr($today->serial_number, 12)) + 1 : 1;

        return env('ORGANIZATION_CODE') . now()->format('Ymd') . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 赋码唯一编号
     * @return HasMany
     */
    final public function PartInstanceIdentityCodes(): HasMany
    {
        return $this->hasMany(PartInstanceExcelTaggingIdentityCode::class, 'part_instance_excel_tagging_report_sn', 'serial_number');
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
}
