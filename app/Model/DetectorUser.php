<?php

namespace App\Model;

use Illuminate\Support\Carbon;

/**
 * Class DetectorUser
 * @package App\Model
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $name
 * @property string $secret_key
 * @property string $access_key
 */
class DetectorUser extends Base
{
    protected $guarded = [];
}
