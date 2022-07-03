<?php


namespace App\Validations\Api\Detector;

use App\Validations\Validation;
use Illuminate\Http\Request;

class LoginValidation extends Validation
{
    public $rules = [
        'account' => ['required', 'between:2,32',],
        'password' => ['required', 'between:6,32',]
    ];
    public $messages = [];
    public $attributes = [
        'account' => '用户名',
        'password' => '密码',
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}