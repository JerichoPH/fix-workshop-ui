<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\SendRepairInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $material_unique_code 物资编码
 * @property string $send_repair_unique_code 送修单编码
 * @property string $repair_report_url 送修报告路径
 * @property string $repair_remark 送修备注
 * @property int $fault_status 故障状态：0没有操作、1合格、2无法恢复、3建议报废
 * @property int $is_check 是否验收，0未验收，1验收
 * @property string $repair_desc 送修描述
 * @property string $repair_report_name 送修报告名称
 * @property string $repair_file_name 送修报告名称
 * @property string $material_type
 * @property-read \App\Model\EntireInstance $WithEntireInstance
 * @property-read \App\Model\PartInstance $WithPartInstance
 * @property-read \App\Model\SendRepair $WithSendRepair
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereFaultStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereIsCheck($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereMaterialUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereRepairDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereRepairFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereRepairRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereRepairReportName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereRepairReportUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereSendRepairUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\SendRepairInstance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SendRepairInstance extends Model
{
    protected $fillable = [
        'material_unique_code',
        'send_repair_unique_code',
        'repair_report_url',
        'repair_report_name',
        'repair_remark',
        'repair_desc',
        'fault_status',
        'material_type',
        'is_check'
    ];

    public static $FAULT_STATUS = [
        0 => '未选择',
        1 => '合格',
        2 => '无法恢复',
        3 => '建议报废'
    ];

    public function getFaultStatusAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$FAULT_STATUS[$value],
        ];
    }

    public static $MATERIAL_TYPES = [
        'ENTIRE' => '整件',
        'PART' => '部件'
    ];

    public function getMaterialTypeAttribute($value)
    {
        return [
            'text' => self::$MATERIAL_TYPES[$value],
            'value' => $value
        ];
    }

    public function WithEntireInstance()
    {
        return $this->belongsTo(EntireInstance::class, 'material_unique_code', 'identity_code');
    }

    public function WithPartInstance()
    {
        return $this->belongsTo(PartInstance::class, 'material_unique_code', 'identity_code');
    }

    public function WithSendRepair()
    {
        return $this->belongsTo(SendRepair::class, 'send_repair_unique_code', 'unique_code');
    }
}
