<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallRoomStoreRequest extends FormRequest
{
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
        return [
            'type' => [
                'required',
            ],
            'station_unique_code' => [
                'required',
                'size:6',
            ],
            'unique_code' => [
                'required',
                'between:0,13',
                Rule::unique('install_rooms'),
            ],

        ];
    }

    public function messages()
    {
        return [
            'type.required' => '请选择机房',
            'station_unique_code.required' => '请选择到车站',
            'station_unique_code.size' => '车站编码必须为5位',
            'unique_code.required' => '编码不能为空',
            'unique_code.between' => '编码不能小于0位或大于2位',
            'unique_code.unique' => '该车站已添加机房，请勿重复添加',
        ];
    }
}