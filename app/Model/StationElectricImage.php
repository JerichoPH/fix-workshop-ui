<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class StationElectricImage
 * @package App\Model
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $original_filename 原始文件名
 * @property string|null $original_extension 原始扩展名
 * @property string|null $filename 保存文件名
 * @property string|null $maintain_station_unique_code 所属车站代码
 * @property int|null $sort 排序
 */
class StationElectricImage extends Model
{
    protected $guarded = [];

    /**
     * 车站
     * @return HasOne
     */
    final public function Station(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'maintain_station_unique_code');
    }
}
