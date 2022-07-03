<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\CollectionOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $unique_code 编码
 * @property int $third_party_user_id 第三方用户id
 * @property string $excel_url excel文件路径
 * @property string $paragraph_unique_code
 * @property string $type 类型，MATERIAL数据，LOCATION定位
 * @property-read \App\Model\ThirdPartyUser $WithStationInstallUser
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereExcelUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereParagraphUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereThirdPartyUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\CollectionOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CollectionOrder extends Model
{
    protected $guarded = [];

    public static $TYPE = [
        'MATERIAL' => '数据采集',
        'LOCATION' => '器材定位',
        'STATION' => '所编号定位车站',
    ];

    public function getTypeAttribute($value)
    {
        return (object)[
            'value' => $value,
            'text' => self::$TYPE[$value]
        ];
    }


    /**
     * 生成唯一编号
     * @param $station_install_user_id
     * @return string
     */
    final public function getUniqueCode($station_install_user_id): string
    {
        $unique_code = time() . '_' . $station_install_user_id;
        if (!empty(DB::table('collection_orders')->where('unique_code', $unique_code)->first()))
            self::getUniqueCode($station_install_user_id);

        return $unique_code;
    }

    /**
     * 生成唯一编号
     * @param $station_install_user_id
     * @return string
     */
    final public static function generateUniqueCode($station_install_user_id): string
    {
        $unique_code = time() . '_' . $station_install_user_id;
        if (!empty(DB::table('collection_orders')->where('unique_code', $unique_code)->first()))
            self::getUniqueCode($station_install_user_id);

        return $unique_code;
    }

    final public function WithStationInstallUser()
    {
        return $this->belongsTo(StationInstallUser::class, 'station_install_user_id', 'id');
    }

    final public function WithCollectionOrderEntireInstances()
    {
        return $this->hasMany(CollectionOrderEntireInstance::class, 'collection_order_unique_code', 'unique_code');
    }

}
