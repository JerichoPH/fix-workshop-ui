<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckPlanStoreRequest extends FormRequest
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
            'check_project_id' => [
                'required',
            ],
            'station_unique_code' => [
                'required',
            ],
            'expiring_at' => [
                'required',
            ],
        ];
    }

    public function messages()
    {
        return [
            'check_project_id.required' => '项目名称不能为空',
            'station_unique_code.required' => '车站不能为空',
            'expiring_at.required' => '截止时间不能为空',
        ];
    }
}
