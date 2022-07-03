<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\OutEntireInstanceCorrespondence
 *
 * @property int $id
 * @property string $old
 * @property string $new
 * @property string $location
 * @property string $station
 * @property string $new_tid
 * @property string $old_tid
 * @property string $out_warehouse_sn 出所单编号
 * @property string $is_scan 是否扫码：0未扫码，1扫码
 * @property int $account_id 操作人
 * @property-read \App\Model\EntireInstance $WithEntireInstanceNew
 * @property-read \App\Model\EntireInstance $WithEntireInstanceOld
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereIsScan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereNew($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereNewTid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereOld($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereOldTid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereOutWarehouseSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\OutEntireInstanceCorrespondence whereStation($value)
 * @mixin \Eloquent
 */
class OutEntireInstanceCorrespondence extends Model
{

    protected $guarded = [];

    public $timestamps = false;

    public function WithEntireInstanceNew()
    {
        return $this->belongsTo(EntireInstance::class, 'new', 'identity_code');
    }

    public function WithEntireInstanceOld()
    {
        return $this->belongsTo(EntireInstance::class, 'old', 'identity_code');
    }

}
