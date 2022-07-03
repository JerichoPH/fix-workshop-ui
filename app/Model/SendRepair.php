<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\SendRepair
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $unique_code
 * @property string $state 送修状态：
 *   START -- 开始
 *   HANDLEING -- 处理中
 *   END -- 已完成
 *   CANCEL -- 作废
 * @property string $type 送修类型
 *          WTW -- 车间到车间
 *          WTF -- 车间到供应商
 *          STW -- 车站到车间
 *          STF -- 车站到供应商
 *          WT -- 车间到~
 *          ST -- 车站到~
 *          TW -- ~到车间
 *          TF -- ~到供应商
 *          T -- 没有来源去向
 * @property int $account_id 操作人
 * @property string $from_code 来源编码：车站/车间
 * @property string $to_code 去向编码：车间/供应商
 * @property string $to_name 去向联系人
 * @property string $to_phone 去向电话
 * @property int $repair_day 维修时长，天为单位，0没有期限
 * @property string|null $repair_due_at 维修到期时间
 * @property string $repair_list_url 送修单上传报告
 * @property string $repair_list_name 送修单名称
 * @property-read Account $WithAccount
 * @property-read Maintain $WithFromMaintain
 * @property-read Collection|SendRepairInstance[] $WithSendRepairInstance
 * @property-read int|null $with_send_repair_instance_count
 * @property-read Factory $WithToFactory
 * @property-read Maintain $WithToMaintain
 */
class SendRepair extends Model
{
    protected $fillable = [
        'unique_code',
        'state',
        'account_id',
        'from_code',
        'to_code',
        'to_name',
        'to_phone',
        'repair_day',
        'repair_due_at',
        "sign_image",
    ];

    public static $prefix = 'SR';

    public static $STATE = [
        'START' => '开始',
        'HANDLEING' => '处理中',
        'END' => '已完成',
        'CANCEL' => '作废'
    ];

    public function getStateAttribute($value): array
    {
        return [
            'value' => $value,
            'text' => self::$STATE[$value],
        ];
    }

    /**
     * 生成送修唯一编码
     * @return string
     */
    public function getUniqueCode(): string
    {
        $time = date("Ymd", time());
        $sendRepair = DB::table('send_repairs')->orderby('unique_code', 'desc')->select('unique_code')->first();
        if (empty($sendRepair)) {
            $unique_code = self::$prefix . $time . '0001';
        } else {
            if (strstr($sendRepair->unique_code, $time)) {
                $suffix = sprintf("%04d", substr($sendRepair->unique_code, -4) + 1);
                $unique_code = self::$prefix . $time . $suffix;
            } else {
                $unique_code = self::$prefix . $time . '0001';
            }
        }
        return $unique_code;
    }

    public function WithAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function WithSendRepairInstance(): HasMany
    {
        return $this->hasMany(SendRepairInstance::class, 'send_repair_unique_code', 'unique_code');
    }

    public function WithFromMaintain(): BelongsTo
    {
        return $this->belongsTo(Maintain::class, 'from_code', 'unique_code');
    }

    public function WithToFactory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'to_code', 'unique_code');
    }

    public function WithToMaintain(): BelongsTo
    {
        return $this->belongsTo(Maintain::class, 'to_code', 'unique_code');
    }
}
