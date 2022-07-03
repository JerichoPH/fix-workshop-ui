<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StationLocationStoreRequest extends FormRequest
{
    public static $RULES = [
        'line_name' => 'required|between:1,50',
        'maintain_station_name' => 'required|between:1,50',
        'wechat_open_id' => 'required|between:1,50',
    ];

    public static $MESSAGES = [
        'line_name.required' => '线别名称不能能为空',
        'line_name.between' => '线别不能超过50字',
        'maintain_station_name.required' => '车站名称不能为空',
        'maintain_station_name.between' => '车站名称不能超过50字',
        'wechat_open_id.required' => '微信openid不能为空',
        'wechat_open_id.between' => '微信openid不能超过50位',
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
