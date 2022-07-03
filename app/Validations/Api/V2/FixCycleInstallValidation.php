<?php

namespace App\Validations\Api\V2;

use App\Validations\Validation;

class FixCycleInstallValidation extends Validation
{
    public $rules = [
        "old_entire_instance_identity_code" => ["required",],
        "new_entire_instance_identity_code" => ["required",],
    ];
    public $messages = [];
    public $attributes = [
        "old_entire_instance_identity_code" => "下道器材唯一编号",
        "new_entire_instance_identity_code" => "上道器材唯一编号",
    ];
}