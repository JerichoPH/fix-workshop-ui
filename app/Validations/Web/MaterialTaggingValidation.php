<?php

namespace App\Validations\Web;

use App\Validations\Validation;
use Illuminate\Http\Request;

class MaterialTaggingValidation extends Validation
{
    public $rules = [
        'asset_code' => ['required', 'between:1,64',],
        'fixed_asset_code' => ['nullable', 'between:1,64',],
        'material_type_identity_code' => ['required', 'between:1,64',],
        'workshop_unique_code' => ['nullable', 'between:1,50',],
        'station_unique_code' => ['nullable', 'between:1,50',],
        'position_unique_code' => ['nullable', 'between:1,50',],
        'work_area_unique_code' => ['nullable', 'between:1,50',],
        'source_type' => ['nullable', 'size:2',],
        'source_name' => ['nullable', 'between:1,50',],
        'number' => ['required', 'min:1',],
    ];

    public $messages = [];

    public $attributes = [
        'asset_code' => '物资编号',
        'fixed_asset_code' => '固资编号',
        'material_type_identity_code' => '物资类型编号',
        'workshop_unique_code' => '车间编号',
        'station_unique_code' => '车站编号',
        'position_unique_code' => '库房位置编号',
        'work_area_unique_code' => '工区编号',
        'source_type' => '来源类型代码',
        'source_name' => '来源名称',
        'number' => '数量',
    ];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}
