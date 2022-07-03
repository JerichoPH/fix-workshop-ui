<?php

namespace App\Validations\Web;

use App\Validations\Validation;
use Illuminate\Http\Request;

class CentreUpdateValidation extends Validation
{
    public $rules = [
        "name" => ["required", "between:1,50",],
        "scene_workshop_unique_code" => ["nullable", "between:1,50",],
    ];

    public $messages = [];

    public $attributes = [
        "name" => "中心名称",
        "scene_workshop_unique_code" => "所属现场车间代码",
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}
