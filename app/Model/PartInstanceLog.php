<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PartInstanceLog
 * @package App\Model
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $name
 * @property string $description
 * @property string $part_instance_identity_code
 * @property string $type
 * @property string $url
 * @property string $material_type
 * @property int $operator_id
 * @property string $station_unique_code
 * @property-read PartInstance $PartInstance
 * @property-read Account $Operator
 * @property-read Station $Station
 */
class PartInstanceLog extends Model
{
    protected $guarded = [];

    public static $ICONS = [
        'fa-envelope-o',  # 0普通消息
        'fa-home',  # 1出入所
        'fa-wrench',  # 2检修
        'fa-link',  # 3RFID绑定
        'fa-map-signs',  # 4上下道
        'fa-exclamation',  # 5现场故障描述或入所故障描述、监测报警
        'fa-envelope-o', # 6生产日期类说明
    ];

    final public function PartInstance():HasOne
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'part_instance_identity_code');
    }

    final public function Operator(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'operator_id');
    }

    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'station_unique_code');
    }
}
