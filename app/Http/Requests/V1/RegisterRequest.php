<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public static $RULES = [
        'account' => 'required|between:2,191|unique:accounts',
        'password' => 'required|between:6,25',
        'nickname' => 'nullable|between:2,191',
        'email' => 'nullable|email|between:2,191',
        'phone' => 'nullable|regex:/^1\d{10}$/|size:11',
        'status_id' => 'nullable|integer|min:1',
        'open_id' => 'nullable|size:32',
        'organization_id' => 'nullable|integer|min:1',
        // 'temp_task_position' => 'required',
        'rank' => 'required',
    ];

    public static $MESSAGES = [
        'account.required' => '账号不能为空',
        'account.between' => '账号不能小于2位或大于191位',
        'account.unique' => '账号被占用',
        'password.required' => '密码不能为空',
        'password.between' => '密码不能小于5位或大于25位',
        'nickname.between' => '昵称不能小于2位或大于191位',
        'email.email' => '邮箱地址格式不正确',
        'email.between' => '邮箱地址不能小于2位或大于191位',
        'phone.regex' => '手机号格式不正确',
        'phone.size' => '手机号长度必须是11位',
        'status_id.integer' => '状态必须是数字',
        'status_id.min' => '状态不能小于1',
        'open_id.size' => '开放编号必须是32位',
        'organization_id.integer' => '机构编号必须是数字',
        'organization_id.min' => '机构编号不能小于1',
        // 'temp_task_position.required' => '未选择临时生产任务职务',
        'rank' => '职务不能为空',
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
