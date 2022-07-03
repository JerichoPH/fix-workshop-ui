<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CheckPlan extends Model
{
    protected $fillable = [
        'serial_number',
        'status',
        'check_project_id',
        'station_unique_code',
        'unit',
        'expiring_at',
        'number',
        'account_id',
    ];
    public static $STATUS = [
        0 => '计划开始',
        1 => '任务结束',
        2 => '设备分配中',
        3 => '任务进行中',
    ];

    public function getStatusAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$STATUS[$value]
        ];
    }

    /**
     * 生成新任务单号
     * @param string $scene_workshop_unique_code
     * @return string
     */
    final public static function generateSerialNumber(string $scene_workshop_unique_code): string
    {
        $last_order = CheckPlan::with([])->orderByDesc('id')->first();
        if ($last_order) {
            $max_sn = intval(Str::substr($last_order->serial_number, -4));
        } else {
            $max_sn = 0;
        }
        $new_sn = str_pad($max_sn + 1, 4, '0', 0);
        return $scene_workshop_unique_code . date('Ymd') . $new_sn;
    }

    final public function WithAccount()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    final public function WithCheckProject()
    {
        return $this->belongsTo(CheckProject::class, 'check_project_id', 'id');
    }

    final public function WithStation()
    {
        return $this->belongsTo(Maintain::class, 'station_unique_code', 'unique_code')->where('type', 'STATION');
    }

    final public function CheckPlanEntireInstances()
    {
        return $this->hasMany(CheckPlanEntireInstance::class, 'check_plan_serial_number', 'serial_number');
    }
}
