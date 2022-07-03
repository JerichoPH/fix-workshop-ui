<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\FixWorkflowDetectingErrorLog
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $platform
 * @property string|null $organization_code
 * @property string|null $testing_device_id
 * @property string|null $entire_instance_serial_number
 * @property string|null $entire_instance_identity_code
 * @property string|null $error_description
 * @property string|null $detecting_data
 * @property-read \App\Model\EntireInstance $EntireInstance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereDetectingData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereEntireInstanceSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereErrorDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereOrganizationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereTestingDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\FixWorkflowDetectingErrorLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FixWorkflowDetectingErrorLog extends Model
{
    protected $guarded = [];

    public function EntireInstance()
    {
        return $this->hasOne(EntireInstance::class, 'identity_code', 'entire_instance_identity_code');
    }
}
