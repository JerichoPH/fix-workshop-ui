<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallQRequest extends FormRequest
{
    public static $RULES = [
        'maintain_station_name' => 'required',
        'maintain_location_code' => 'required',
        'is_main' => 'required|in:1,0',
//        'last_installed_time' => 'required',
    ];

    public static $MESSAGES = [
        'maintain_station_name.required' => '站名称不能为空',
        'maintain_location_code.required' => '组合位置不能为空',
        'is_main.required' => '主备用状态不能为空',
        'is_main.in' => '主备用状态只能是1或0',
//        'last_installed_time.required' => '安装时间不能为空',
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
    final public function rules()
    {
        return self::$RULES;
    }

    final public function messages()
    {
        return self::$MESSAGES;
    }
}
