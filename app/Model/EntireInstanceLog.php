<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\EntireInstanceLog
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $name
 * @property string|null $description
 * @property string $entire_instance_identity_code
 * @property int $type 0：普通描述
 * 1：出入所
 * 2：检修相关
 * 3:绑定RFID相关
 * 4：上下道
 * @property string $url
 * @property string $material_type ENTIRE整件、PART部件
 * @property int $operator_id 操作人
 * @property string $station_unique_code 车站
 * @property-read EntireInstance $EntireInstance
 * @property-read Account $Operator
 * @property-read Maintain $Station
 */
class EntireInstanceLog extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static $ICONS = [
        'fa-envelope-o',  # 0普通消息
        'fa-home',  # 1出入所
        'fa-wrench',  # 2检修
        'fa-link',  # 3RFID绑定
        'fa-map-signs',  # 4上下道
        'fa-exclamation',  # 5现场故障描述或入所故障描述、监测报警
        'fa-envelope-o', # 6生产日期类说明
        'fa-link', # 7安装部件
        'fa-unlink', # 8拆除部件
        'fa-img', # 9室外采集图片
    ];

    final public function EntireInstance():HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
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
