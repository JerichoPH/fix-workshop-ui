<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThirdPartyRequest extends FormRequest
{
    public static $RULES = [
        'username' => 'required|between:2,50',
        'password' => 'required|between:6,16'
    ];

    public static $MESSAGES = [
        'username.required' => '账号不能为空',
        'username.between' => '账号不能小于2位或大于50位',
        'password.required' => '密码不能为空',
        'password.between' => '密码不能小于6位或大于16位',
    ];


    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return self::$RULES;
    }

    public function messages()
    {
        return self::$MESSAGES;
    }
}
