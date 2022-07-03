<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StuffTypeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'unique_code' => [
                'required',
                'between:0,100',
                Rule::unique('stuff_types'),
            ],
            'name' => [
                'required',
                'between:0,50',
                Rule::unique('stuff_types'),
            ],
            'unit' => [
                'required',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'unique_code.required' => '物资编号不能为空',
            'unique_code.unique' => '物资编号不能重复',
            'unique_code.between' => '物资编号不能小于0位或大于100位',
            'name.required' => '名称不能为空',
            'name.unique' => '名称不能重复',
            'name.between' => '物资编号不能小于0位或大于50位',
            'unit.between' => '单位不能为空',
        ];
    }
}