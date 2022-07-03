<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallTierStoreRequest extends FormRequest
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
            'name' => [
                'required',
                'between:0,20'
            ],
            'unique_code' => [
                'required',
                'between:0,19',
                Rule::unique('install_tiers'),
            ],
            'install_shelf_unique_code' => [
                'required',
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '名称不能为空',
            'name.between' => '名称不能小于0位或大于20位',
            'name.unique' => '名称不能重复',
            'unique_code.required' => '编码不能为空',
            'unique_code.between' => '编码不能小于0位或大于2位',
            'unique_code.unique' => '编码不能重复',
            'install_shelf_unique_code.required' => '请选择架（柜）',
        ];
    }
}