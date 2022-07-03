<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class EquipmentCabinet
 * @package App\Model
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 编辑时间
 * @property string $name 名称
 * @property string $unique_code 编号
 * @property string $entire_instance_identity_code 设备编号
 */
class EquipmentCabinet extends Model
{
    protected $guarded = [];

    /**
     * 房间类型
     * @var string[]
     */
    public static $ROOM_TYPES = [
        'MECHANICAL' => '机械室',
        'POWER_SUPPLY' => '电源室',
    ];

    /**
     * 房间类型编号
     * @var string[]
     */
    public static $ROOM_TYPE_NUMBERS = [
        'MECHANICAL' => '10',  // 机械室
        'POWER_SUPPLY' => '12',  // 电源室
    ];

    /**
     * 生成唯一编号
     * @param string $room_type 房间类型
     * @param string $maintain_station_unique_code 车站代码
     * @param int $row 排
     * @return string
     */
    final public static function generateUniqueCode(string $room_type, string $maintain_station_unique_code, int $row)
    {
        $last = self::with([])
            ->orderByDesc('id')
            ->where('room_type', $room_type)
            ->where('maintain_station_unique_code', $maintain_station_unique_code)
            ->first();
        $last_unique_code = $last ? intval(substr($last->unique_code, 13)) : 0;
        $room_type_number = self::$ROOM_TYPE_NUMBERS[$room_type];
        $paragraph_code = env('ORGANIZATION_CODE');
        return "W{$room_type_number}{$paragraph_code}{$maintain_station_unique_code}"
            . str_pad($row, 2, '0', STR_PAD_LEFT)
            . str_pad($last_unique_code + 1, 2, '0', STR_PAD_LEFT);
    }

    /**
     * 获取房间类型
     * @param $value
     * @return object
     */
    final public function getRoomTypeAttribute($value)
    {
        return (object)['code' => $value, 'name' => self::$ROOM_TYPES[$value]];
    }

    /**
     * 获取房间类型编号
     * @param $value
     * @return object
     */
    final public function getRoomTypeNumberAttribute($value)
    {
        return (object)['code' => $value, 'number' => self::$ROOM_TYPE_NUMBERS[$value]];
    }

    /**
     * 所属设备
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }

    /**
     * 所属车站
     * @return HasOne
     */
    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }

    /**
     * 组合位置
     * @return HasMany
     */
    final public function CombinationLocations(): HasMany
    {
        return $this->hasMany(CombinationLocation::class, 'equipment_cabinet_unique_code', 'unique_code');
    }
}
