<?php

namespace App\Validations\Web;

use App\Validations\Validation;
use Illuminate\Http\Request;

class MaterialAppendValidation extends Validation
{
    public $rules = [
        'material_type_identity_code' => ['required', 'between:1,50',],
        'number' => ['required', 'min:1',],
        'operation_direction' => ['required', 'in:IN,OUT',],
    ];

    public $messages = [];

    public $attributes = [
        'identity_code' => '物资编码',
        'number' => '数量',
        'operation_direction' => '操作类型',
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}
