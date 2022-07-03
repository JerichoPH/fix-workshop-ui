<?php

namespace App\Http\Controllers\Part;

use App\Model\Category;
use App\Model\EntireModel;
use App\Model\PartCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\PartModel;

class KindController extends Controller
{
    /**
     * 部件种类型列表
     */
    final public function index()
    {
        try {
            // 设备获取种类
            $categories_S = Category::with([])->where('unique_code', 'like', 'S%')->get();
            $current_category_unique_code_S = request('category_unique_code', $categories_S->isNotEmpty() ? $categories_S->first()->unique_code : '');

            // 获取器材种类
            $categories_Q = Category::with([])->where('unique_coce', 'like', 'Q%')->get();
            $current_category_unique_code_Q = request('category_unique_code', $categories_Q->isNotEmpty() ? $categories_Q->first()->unique_code : '');

            // 获取部件种类
            $part_categories = PartCategory::with([])->where('category_unique_code', $current_category_unique_code_S)->get();
            $current_part_category_unique_code = request('part_category_unique_code', $part_categories->isNotEmpty() ? $part_categories->first()->unique_code : '');

            // 获取类型
            $entire_models = EntireModel::with([])->where('category_unique_code', 'like', 'S%')->where('is_sub_model', false)->get();
            $current_entire_model_unique_code = request('entire_model_unique_code', $entire_models->isNotEmpty() ? $entire_models->first()->unique_code : '');

            // 获取代码字典
            $part_models = PartModel::with([])->where('entire_model_unique_code', $current_entire_model_unique_code)->get();
            $current_part_model_unique_code = request('part_model_unique_code', $part_models->isNotEmpty() ? $part_models->first()->unique_code : '');

            // 获取器材种类

            return view('Part.Kind.index', [
                'categories_S' => $categories_S,
                'entire_models' => $entire_models,
                'part_models' => $part_models,
                'current_category_unique_code' => $current_category_unique_code,
                'current_entire_model_unique_code' => $current_entire_model_unique_code,
                'current_sub_model_unique_code' => $current_part_model_unique_code,
                // 'device_categories_as_json' => $device_categories_as_json,
                // 'device_entire_models_by_category_unique_code_as_json' => $device_entire_models_by_category_unique_code_as_json,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }
}
