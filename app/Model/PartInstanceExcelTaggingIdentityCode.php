<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PartInstanceExcelTaggingInstanceCode
 * @package App\Model
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $part_instance_excel_tagging_report_sn
 * @property string $part_instance_identity_code
 * @property-read PartInstanceExcelTaggingReport $PartInstanceExcelTaggingReport
 * @property-read PartInstance $PartInstance
 */
class PartInstanceExcelTaggingIdentityCode extends Model
{
    protected $guarded = [];

    /**
     * excel赋码单
     * @return HasOne
     */
    final public function PartInstanceExcelTaggingReport(): HasOne
    {
        return $this->hasOne(PartInstanceExcelTaggingReport::class, 'serial_number', 'part_instance_excel_tagging_report_sn');
    }

    /**
     * 设备器材
     * @return HasOne
     */
    final public function PartInstance(): HasOne
    {
        return $this->hasOne(PartInstance::class, 'identity_code', 'part_instance_identity_code');
    }
}
