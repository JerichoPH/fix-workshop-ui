<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\WarehouseStorageBatchReport
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $identity_code 唯一编号
 * @property string|null $factory_device_code 厂编号
 * @property string|null $serial_number 所编号
 * @property string|null $rfid_code RFID TID
 * @property string|null $category_unique_code 种类代码
 * @property string|null $category_name 种类名称
 * @property string|null $entire_model_unique_code 类型代码
 * @property string|null $entire_model_name 种类名称
 * @property string|null $maintain_station_name 站名称
 * @property string|null $maintain_location_code 安装位置码
 * @property string|null $to_direction 去向
 * @property string|null $traction 牵引
 * @property string|null $open_direction 开向
 * @property string|null $said_rod 表示杆特征
 * @property string|null $line_name 线制
 * @property string|null $crossroad_number 道岔号
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereEntireModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereFactoryDeviceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereRfidCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereToDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\WarehouseStorageBatchReport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WarehouseStorageBatchReport extends Model
{
    protected $guarded = [];
}
