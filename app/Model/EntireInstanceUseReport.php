<?php

namespace App\Model;

use App\Model\Install\InstallPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use stdClass;

/**
 * Class EntireInstanceUseReport
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $entire_instance_identity_code
 * @property string $scene_workshop_unique_code
 * @property string $maintain_station_unique_code
 * @property string $maintain_location_code
 * @property string $processor_id
 * @property string $crossroad_number
 * @property string $open_direction
 * @property string $status
 * @property object $type
 * @property string $maintain_section_name
 * @property string $maintain_send_or_receive
 * @property-read EntireInstance $EntireInstance
 * @property-read Maintain $SceneWorkshop
 * @property-read Maintain $Station
 * @property-read InstallPosition $InstallPosition
 * @property-read Account $Processor
 */
class EntireInstanceUseReport extends Model
{
    protected $guarded = [];

    public static $TYPES = [
        'INSTALLED' => '上道',
        'INSTALLING' => '现场备品入柜',
        'UNINSTALL' => '下道',
    ];

    /**
     * 生成id
     * @return string
     */
    final public static function generateId(): string
    {
        return md5((time() * 10) . (rand(0, 9999)));
    }

    /**
     * 设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * 现场车间
     * @return HasOne
     */
    final public function SceneWorkshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'scene_workshop_unique_code');
    }

    /**
     * 现场车站
     */
    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }

    /**
     * 室内组合位置
     * @return HasOne
     */
    final public function InstallPosition(): HasOne
    {
        return $this->hasOne(InstallPosition::class, 'unique_code', 'maintain_location_code');
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
     * 获取类型名称
     * @param $value
     * @return object
     */
    final public function getTypeAttribute($value):stdClass
    {
        return (object)[
            'code' => $value,
            'name' => self::$TYPES[$value] ?? '无',
        ];
    }
}
