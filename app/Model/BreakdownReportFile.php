<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\BreakdownReportFile
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $filename
 * @property string $source_filename
 * @property string $ex_name 文件扩展名
 * @property int $breakdown_order_entire_instance_id 故障设备id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereBreakdownOrderEntireInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereExName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereSourceFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\BreakdownReportFile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BreakdownReportFile extends Model
{
    protected $guarded = [];
}
