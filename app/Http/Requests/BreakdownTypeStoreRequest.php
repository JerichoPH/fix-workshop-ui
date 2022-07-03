<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BreakdownTypeStoreRequest extends FormRequest
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
                Rule::unique('breakdown_types')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '故障类型名称不能为空',
            'name.unique' => '故障类型名称不能重复',
        ];
    }
}
