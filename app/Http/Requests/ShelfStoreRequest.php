<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShelfStoreRequest extends FormRequest
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
                Rule::unique('shelves')->where(function ($query) {
                    $query->where('platoon_unique_code', $_POST['platoon_unique_code']);
                }),
            ],
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
