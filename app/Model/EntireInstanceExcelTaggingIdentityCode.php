<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EntireInstanceExcelTaggingIdentityCode extends Model
{
    protected $guarded = [];

    /**
     * excel赋码单
     * @return HasOne
     */
    final public function EntireInstanceExcelTaggingReport(): HasOne
    {
        return $this->hasOne(EntireInstanceExcelTaggingReport::class, 'serial_number', 'entire_instance_excel_tagging_report_sn');
    }

    /**
     * 设备器材
     * @return HasOne
     */
    final public function EntireInstance(): HasOne
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
