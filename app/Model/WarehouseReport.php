<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * App\Model\WarehouseReport
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $processor_id 经手人编号
 * @property string|null $processed_at 执行时间
 * @property string|null $connection_name 联系人
 * @property string|null $connection_phone 联系电话
 * @property string $type 类型：BUY_IN新购、FIXING检修、ROTATE轮换中、FACTORY_RETURN返厂入所、INSTALL安装、RETURN_FACTORY返厂、SCRAP报废、BATCH_WITH_OLD旧设备批量导入、HIGH_FREQUENCY高频修
 * @property string $direction 方向
 * @property string $serial_number 流水号
 * @property string|null $scene_workshop_name
 * @property string|null $station_name
 * @property int|null $work_area_id 1转辙机工区
 * 2继电器工区
 * 3综合工区
 * @property string $scene_workshop_unique_code
 * @property string $maintain_station_unique_code 车站
 * @property string $status 状态
 * @property string $v250_task_order_sn
 * @property-read Account $Processor
 * @property-read Collection|WarehouseReportEntireInstance[] $WarehouseReportEntireInstances
 * @property-read int|null $warehouse_report_entire_instances_count
 * @property string|null $next_operation 后续操作
 * @property-read Workshop $Workshop 所属车间
 * @property-read Maintain $Station 所属车站
 * @property-read WorkArea $WorkArea 所属工区
 */
class WarehouseReport extends Model
{
    use SoftDeletes;

    public static $TYPE = [
        'NORMAL' => '通用',
        'BUY_IN' => '采购入所',
        'INSTALLING' => '备品',
        'INSTALLED' => '已安装',
        'FIXING' => '检修',
        'ROTATE' => '轮换中',
        'FACTORY_RETURN' => '返厂中',
        'INSTALL' => '安装',
        'RETURN_FACTORY' => '返厂回所',
        'SCRAP' => '报废',
        'BATCH_WITH_OLD' => '批量导入旧设备',
        'HIGH_FREQUENCY' => '高频/状态修',
        'BREAKDOWN' => '故障修',
        'EXCHANGE_MODEL' => '更换型号',
        'NEW_STATION' => '新站',
        'FILL_FIX' => '大修',
        'EXCHANGE_STATION' => '站改',
        'STATION_REMOULD' => '站改',
        'TECHNOLOGY_REMOULD' => '技改',
        'SCENE_BACK_IN' => '现场退回',
    ];

    public static $DIRECTION = [
        'IN' => '入所',
        'OUT' => '出所',
    ];

    protected $guarded = [];

    /**
     * 获取原始数据
     * @param $attributeKey
     * @return mixed
     */
    final public function prototype($attributeKey)
    {
        return @$this->attributes[$attributeKey];
    }

    /**
     * 类型
     * @param $value
     * @return string
     */
    final public function getTypeAttribute($value): string
    {
        return @self::$TYPE[$value] ?: '无';
    }

    /**
     * 方向
     * @param $value
     * @return string
     */
    final public function getDirectionAttribute($value): string
    {
        return @self::$DIRECTION[$value] ?: '无';
    }

    /**
     * 经办人
     * @return HasOne
     */
    final public function Processor(): HasOne
    {
        return $this->hasOne(Account::class, 'id', 'processor_id');
    }

    /**
     * 所属车间
     * @return HasOne
     */
    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, "name", "scene_workshop_name");
    }

    /**
     * 所属车站
     * @return HasOne
     */
    final public function Station():HasOne
    {
        return $this->hasOne(Maintain::class,"name","station_name");
    }

    /**
     * 所属工区
     * @return HasOne
     */
    final public function WorkArea():HasOne
    {
        return $this->hasOne(WorkArea::class,"unique_code","work_area_unique_code");
    }

    /**
     * 出入所单设备器材
     * @return HasMany
     */
    final public function WarehouseReportEntireInstances(): HasMany
    {
        return $this->hasMany(WarehouseReportEntireInstance::class, 'warehouse_report_serial_number', 'serial_number');
    }

    /**
     * 保存出入所签字照片
     * @param string $sign_image
     * @return array
     * @throws \Throwable
     */
    final public function saveSignImage(string $sign_image): array
    {
        preg_match('/^(data:\s*image\/(\w+);base64,)/', $sign_image, $result);
        $extension = $result[2];
        $image = str_replace($result[1], '', $sign_image);
        $filename = "{$this->attributes['serial_number']}.{$extension}";

        Storage::disk('warehouseSignImages')->put($filename, base64_decode($image));
        $sign_image_url = "/storage/warehouseSignImages/{$filename}";

        $this->fill(['sign_image_url' => $sign_image_url])->saveOrFail();
        return ['save_result' => true, 'url' => $sign_image_url];
    }

    // /**
    //  * 上传图片
    //  * @param Request $request
    //  * @return mixed
    //  */
    // final public function uploadImageBase64(Request $request)
    // {
    //     try {
    //         $image_path = $request->get('image_path');
    //         preg_match('/^(data:\s*image\/(\w+);base64,)/', $image_path, $result);
    //         $suffix = $result[2];
    //         $image = str_replace($result[1], '', $image_path);
    //         $imagePath = '/images/test' . str_random(10) . '.' . $suffix;
    //         Storage::disk('public')->put($imagePath, base64_decode($image));
    //         return JsonResponseFacade::data([
    //             'imagePath' => $imagePath
    //         ]);
    //     } catch (ModelNotFoundException $exception) {
    //         return JsonResponseFacade::errorEmpty();
    //     } catch (\Exception $exception) {
    //         return JsonResponseFacade::errorException($exception);
    //     }
    // }
}
