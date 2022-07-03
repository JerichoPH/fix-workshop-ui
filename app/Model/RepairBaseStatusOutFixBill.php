<?php

namespace App\Model;

use App\Facades\TextFacade;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class RepairBaseStatusOutFixBill
 * @package App\Model
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $serial_number
 * @property int $operator_id
 * @property string $status
 * @property int $year
 * @property int $month
 * @property string $work_area_unique_code
 * @property string $station_unique_code
 * @property-read WorkArea $WorkArea
 * @property-read Maintain $SceneWorkshop
 * @property-read Maintain $Station
 */
class RepairBaseStatusOutFixBill extends Model
{
    protected $guarded = [];
    protected $times = [
        'created_at',
        'updated_at',
    ];

    public static $STATUSES = [
        'ORIGIN' => '进行中',
        'FINISH' => '已完成',
        'CLOSE' => '已关闭',
    ];

    /**
     * 生成流水单号
     * @param int $year
     * @param int $month
     * @param string $station_unique_code
     * @return string
     * @throws \Exception
     */
    final public static function generateSerialNumber(int $year, int $month, string $station_unique_code): string
    {
        $work_area_unique_code = session('account.work_area_unique_code');
        if (!$work_area_unique_code) {
            throw new \Exception('当前用户没有所属工区');
        }

        $sn = "{$work_area_unique_code}{$station_unique_code}{$year}{$month}";

        $last = self::with([])
            ->where('serial_number', 'like', "{$sn}%")
            ->orderByDesc('serial_number')
            ->first();
        if ($last) {
            $last_serial_number = str_replace($sn, '', $last->serial_number);
            $next_serial_number = TextFacade::to36(TextFacade::from36($last_serial_number) + 1);
            $next_serial_number = str_pad($next_serial_number, 2, '0', STR_PAD_LEFT);
        } else {
            $next_serial_number = '01';
        }
        return $sn . $next_serial_number;
    }

    final public function getStatusAttribute($value): array
    {
        return [
            'code' => $value,
            'name' => self::$STATUSES[$value] ?? '',
        ];
    }

    final public function WorkAreaUniqueCode(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    final public function SceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'scene_workshop_unique_code');
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }

    final public function RepairBaseStatusOutFixEntireInstances():HasMany
    {
        return $this->hasMany(RepairBaseStatusOutFixEntireInstance::class,'id','bill_id');
    }

}
