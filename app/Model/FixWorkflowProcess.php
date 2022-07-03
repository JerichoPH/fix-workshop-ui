<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Model\FixWorkflowProcess
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $fix_workflow_serial_number 检修工单序列号
 * @property string|null $note 备注
 * @property string $stage
 * @property string|null $type
 * @property string|null $auto_explain 自动注解
 * @property string $serial_number
 * @property int $numerical_order
 * @property int $is_allow 是否通过检测
 * @property int|null $processor_id 操作人
 * @property string|null $processed_at 操作时间
 * @property float $temperature_value 环境温度
 * @property string $temperature_unit 环境温度单位
 * @property string|null $part_instance_identity_code
 * @property string $upload_url 上传路径
 * @property string $check_type 检测类型默认DB
 * @property string $upload_file_name 上传文件名字
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @property-read \App\Model\FixWorkflow $FixWorkflow
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\FixWorkflowRecord[] $FixWorkflowRecords
 * @property-read int|null $fix_workflow_records_count
 * @property-read \App\Model\Measurement $Measurement
 * @property-read \App\Model\PartInstance $PartInstance
 * @property-read \App\Model\Account $Processor
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowProcess onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereAutoExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereCheckType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereIsAllow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereNumericalOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess wherePartInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereTemperatureUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereTemperatureValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereUploadFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowProcess whereUploadUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowProcess withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\FixWorkflowProcess withoutTrashed()
 * @mixin \Eloquent
 */
class FixWorkflowProcess extends Model
{
    use SoftDeletes;

    public static $STAGE = [
        'FIX_BEFORE' => '修前检',
        'FIX_AFTER' => '修后检',
        'CHECKED' => '工区验收',
        'WORKSHOP' => '车间抽验',
        'SECTION' => '段技术科抽验',
        'SPOT_CHECK' => '抽验',
        'PROJECT_TEST' => '工程测试',
        'NEW_TEST' => '新设备',
    ];

    public static $TYPE = [
        'ENTIRE' => '整件检修',
        'PART' => '部件检修'
    ];
    public static $CHECK_TYPE = [
        'pdf' => 'PDF',
        'doc' => 'WORD',
        'docx' => 'WORD',
        'jpg' => 'IMAGE',
        'png' => 'IMAGE',
        'jpeg' => 'IMAGE',
        'xls' => 'EXCEL',
        'xlsx' => 'EXCEL',
        'txt' => 'TXT',
        'BINARY' => 'BINARY',
        'json' => 'JSON',
        'db' => 'DB',
    ];


    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    public function PartInstance()
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'part_instance_identity_code');
    }

    public function prototype($attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    public function getStageAttribute($value)
    {
        return @self::$STAGE[$value] ?? '';
    }

    public function prototypeStageAttribute($value = null)
    {
        return array_flip(self::$STAGE)[$value ?: $this->attributes['stage']];
    }

    public function flipStage($value = null)
    {
        return array_flip(self::$STAGE)[$value];
    }

    public function FixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class, 'serial_number', 'fix_workflow_serial_number');
    }

    public function Measurement()
    {
        return $this->hasOne(Measurement::class, 'identity_code', 'measurement_identity_code');
    }

    public function Processor()
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    public function getTypeAttribute($value)
    {
        return [
            'value' => $value,
            'text' => self::$TYPE[$value],
        ];
    }

    public function FixWorkflowRecords()
    {
        return $this->hasMany(FixWorkflowRecord::class, 'fix_workflow_process_serial_number', 'serial_number');
    }
}
