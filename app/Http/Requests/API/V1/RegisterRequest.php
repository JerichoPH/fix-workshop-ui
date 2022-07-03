<?php

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public static $RULES = [
        'account' => 'required|between:2,191|unique:accounts',
        'password' => 'required|between:6,25',
        'nickname' => 'required|between:2,20',
    ];

    public static $MESSAGES = [
        'account.required' => '账号不能为空',
        'account.between' => '账号不能小于2位或大于191位',
        'account.unique' => '账号被占用',
        'password.required' => '密码不能为空',
        'password.between' => '密码不能小于5位或大于25位',
        'nickname.required'=>'姓名不能为空',
        'nickname.between' => '姓名不能小于2位或大于20位',
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
