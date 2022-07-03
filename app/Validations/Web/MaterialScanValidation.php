<?php

namespace App\Validations\Web;

use App\Validations\Validation;
use Illuminate\Http\Request;

class MaterialScanValidation extends Validation
{
    public $rules = [
        'identity_code' => ['required', 'between:1,50',],
        'operation_direction' => ['required', 'in:IN,OUT',],
    ];

    public $messages = [];

    public $attributes = [
        'identity_code' => '物资编码',
        'operation_direction' => '操作类型',
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}
