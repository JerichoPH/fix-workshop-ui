<?php

namespace App\Model\ParagraphCenter;

use App\Model\Base;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class ParagraphCenterMeasurementStep
 * @package App\Model\ParagraphCenter
 * @property string $uuid
 * @property string $created_at
 * @property string $updated_at
 * @property string $paragraph_center_measurement_sn
 *
 * @property int $sort
 * @property string $data
 */
class ParagraphCenterMeasurementStep extends Base
{
    protected $guarded = [];

    /**
     * 所属检修模板
     * @return HasOne
     */
    final public function ParagraphCenterMeasurement(): HasOne
    {
        return $this->hasOne(ParagraphCenterMeasurement::class, "serial_number", "paragraph_center_measurement_sn");
    }
}
