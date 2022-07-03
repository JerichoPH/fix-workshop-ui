<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepairBaseStationReformOrderRequest extends FormRequest
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
            'station_unique_code' => ['required'],
            'scene_workshop_unqiue_code' => ['required'],
            'operator_id' => ['required'],
            'type' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'station_unique_code.required' => '车站不能为空',
            'scene_workshop_unqiue_code.required' => '现场车间不能为空',
            'operator_id.required' => '经办人不能为空',
            'type.required' => '经办人不能为空',
        ];
    }
}
