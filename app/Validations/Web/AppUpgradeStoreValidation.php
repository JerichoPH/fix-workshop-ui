<?php

namespace App\Validations\Web;

use App\Validations\Validation;

class AppUpgradeStoreValidation extends Validation
{
    public $rules = [
        "version" => ["required", "between:1,64",],
        "target" => ["required", "between:1,64",],
        "description" => ["required"],
        "operating_steps" => ["required"],
        "upgrade_reports" => ["nullable"],
    ];

    public $messages = [];

    public $attributes = [
        "version" => "版本",
        "target" => "目标",
        "description" => "更新内容",
        "operating_steps" => "更新步骤",
        "upgrade_reports" => "更新日志",
    ];

    // public function __construct(Request $request)
    // {
    //     parent::__construct($request);
    //
    //     $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    // }
}
