<?php

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class InstalledRequest extends FormRequest
{
    public static $RULES = [
        'operator' => 'required|in:IN,OUT',
        'status' => 'required|in:INSTALLED,INSTALLING,UNINSTALLED',
    ];

    public static $MESSAGES = [
        'operator.required' => '操作模式',
        'operator.in' => '操作模式',
        'status.required' => '设备状态',
        'status.in' => '设备状态',
    ];

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
            //
        ];
    }
}
