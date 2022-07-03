<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TierUpdateRequest extends FormRequest
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
                'between:0,20',
                Rule::unique('tiers')->where(function ($query) {
                    $query->where('shelf_unique_code', $_POST['shelf_unique_code']);
                })->ignore($_POST['id']),
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '名称不能为空',
            'name.between' => '名称不能小于0位或大于20位',
            'name.unique' => '名称不能重复',
        ];
    }
}
