<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\WarehouseInBatchReport
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_instance_identity_code
 * @property string|null $fix_workflow_serial_number
 * @property int $processor_id
 * @property string $maintain_station_name 车站名称
 * @property string $maintain_location_code 组合位置
 * @property string $crossroad_number 道岔号
 * @property string $traction 牵引
 * @property string $line_name 线制
 * @property string $crossroad_type 道岔类型
 * @property int $extrusion_protect 防挤压保护罩
 * @property string $point_switch_group_type 转辙机分组类型
 * @property string $open_direction 开向
 * @property string $said_rod 表示杆特征
 * @property string $direction 扫码类型
 * @property-read EntireInstance $EntireInstance
 * @property-read Account $Processor
 */
class WarehouseInBatchReport extends Model
{
    protected $guarded = [];

    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    final public function Processor():HasOne
    {
        return $this->hasOne(Account::class,'id','processor_id');
    }
}
