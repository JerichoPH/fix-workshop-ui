<?php

namespace App\Validations\Web;

use App\Validations\Validation;
use Illuminate\Http\Request;

class SourceNameStoreValidation extends Validation
{
    public $rules = [
        'name' => ['required', 'between:1,50',],
        'source_type' => ['required', 'size:2',],
    ];

    public $messages = [];

    public $attributes = [
        'name' => '来源名称',
        'source_type' => '来源类型代码',
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}
