<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntireModelStoreRequest extends FormRequest
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
                'between:1,100',
                Rule::unique('entire_models')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
            ],
            'unique_code' => [
                'required',
                'between:1,100',
                Rule::unique('entire_models')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '名称不能为空',
            'name.between' => '名称不能小于1位或大于100位',
            'name.unique' => '名称不能重复',
            'unique_code.required' => '编码不能为空',
            'unique_code.between' => '编码不能小于1位或大于100位',
            'unique_code.unique' => '编码不能重复',
        ];
    }
}
