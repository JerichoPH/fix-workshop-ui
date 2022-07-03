<?php

namespace App\Model;

use App\Model\Install\InstallPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Model\RepairBaseBreakdownOrderTempEntireInstance
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $operator_id 操作人
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property string $in_warehouse_breakdown_explain 入所故障描述
 * @property string $station_breakdown_explain 现场故障描述
 * @property string $station_breakdown_submitted_at 现场故障发生时间
 * @property string $station_breakdown_submitter_name 现场故障上报人
 * @property string $warehouse_in_breakdown_note 入所故障描述
 * @property string $workshop_unique_code 车间代码
 * @property string $station_unique_code 车站代码
 * @property string $line_unique_code 线别代码
 * @property string $maintain_location_code 上道位置
 * @property string $crossroad_number 道岔号
 * @property string $open_direction 开向
 * @property-read Collection|PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes[] $BreakdownTypes
 * @property-read int|null $breakdown_types_count
 * @property-read EntireInstance $EntireInstance
 * @property-read Account $Operator
 * @property-read Maintain $Workshop
 * @property-read Maintain $Station
 * @property-read Line $Lined
 * @property-read InstallPosition $InstallPosition
 */
class RepairBaseBreakdownOrderTempEntireInstance extends Model
{
    protected $guarded = [];
    protected $dates = [
        'created_at',
        'updated_at',
        'station_breakdown_submitted_at',
    ];

    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, "identity_code", "entire_instance_identity_code");
    }

    final public function Operator(): HasOne
    {
        return $this->hasOne(Account::class, "id", "operator_id");
    }

    final public function BreakdownTypes(): BelongsToMany
    {
        return $this->belongsToMany(BreakdownType::class, "pivot_breakdown_order_temp_entire_instance_and_breakdown_types", "repair_base_breakdown_order_temp_entire_instance_id","breakdown_type_id");
    }

    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, "unique_code", "workshop_unique_code");
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, "unique_code", "station_unique_code");
    }

    final public function Line(): HasOne
    {
        return $this->hasOne(Line::class, "unique_code", "line_unique_code");
    }

    final public function InstallPosition(): HasOne
    {
        return $this->hasOne(InstallPosition::class, "unique_code", "maintain_location_code");
    }
}
