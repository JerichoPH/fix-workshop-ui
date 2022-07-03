<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallSRequest extends FormRequest
{
    public static $RULES = [
        'purpose' => '',  # 用途
        'warehouse_name' => '',  # 仓库名称
        'location_unique_code' => '',  # 仓库位置
        'to_direction' => '',  # 去向
        'crossroad_number' => '',  # 道岔号
        'traction' => '',  # 牵引
        'source' => '',  # 来源
        'source_crossroad_number' => '',  # 来源道岔号
        'source_traction' => '',  # 来源牵引
        'line_unique_code' => '',  # 线制
        'open_direction' => '',  # 开向
        'said_rod' => '',  # 标识杆特征
    ];

    public static $MESSAGES = [];

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
