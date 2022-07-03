<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckProjectUpdateRequest extends FormRequest
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
                'between:2,100',
                Rule::unique('check_projects')->where(function ($query) {
                    $query->where('type', $_POST['type']);
                })->ignore($_POST['id']),
            ],
            'type' => [
                'required',
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '名称不能为空',
            'name.between' => '名称不能小于2位或大于100位',
            'name.unique' => '名称不能重复',
            'type.required' => '类型不能为空',
        ];
    }
}
