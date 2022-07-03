<?php

namespace App\Validations\Web;

use App\Validations\Validation;
use Illuminate\Http\Request;

class MaterialTypeStoreValidation extends Validation
{
    public $rules = [
        'identity_code' => ['required', 'between:1,50', 'unique:material_types',],
        'name' => ['required', 'between:1,50', 'unique:material_types',],
        'unit' => ['required', 'between:1,50',],
    ];

    public $messages = [];

    public $attributes = [
        'identity_code' => '物资编码',
        'name' => '材料类型名称',
        'unit' => '单位',
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}
