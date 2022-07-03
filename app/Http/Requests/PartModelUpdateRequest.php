<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartModelUpdateRequest extends FormRequest
{
    public static $RULES = [
        "name" => "bail|required|between:1,50",
        "category_unique_code" => "bail|required|size:3",
        "entire_model_unique_code" => "bail|required|size:5",
        "part_category_id" => "bail|required|integer",
    ];

    public static $MESSAGES = [
        "name.required" => "名称不能为空",
        "name.between" => "名称不能小于1位或大于50位",
        "category_unique_code.required" => "种代码不能为空",
        "category_unique_code.size" => "种代码必须是3位",
        "entire_model_unique_code.required" => "类代码不能为空",
        "entire_model_unique_code.size" => "类代码必须是5位",
        "part_category_id.required" => "部件种类未选择",
        "part_category_id.integer" => "部件种类格式不正确",
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
