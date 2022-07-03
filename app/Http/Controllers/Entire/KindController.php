<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\FuncNotFoundException;
use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KindController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            // 获取种类列表
            $categories = Category::with([])->get();
            $current_category_unique_code = request('category_unique_code', $categories->isNotEmpty() ? $categories->first()->unique_code : '');

            // 获取类型
            $entire_models = EntireModel::with([])->where('category_unique_code', $current_category_unique_code)->where('is_sub_model', false)->get();
            $current_entire_model_unique_code = request('entire_model_unique_code', $entire_models->isNotEmpty() ? $entire_models->first()->unique_code : '');

            // 获取型号
            $sub_models = EntireModel::with([])->where('parent_unique_code', $current_entire_model_unique_code)->where('is_sub_model', true)->get();
            $current_sub_model_unique_code = request('sub_model_unique_code', $sub_models->isNotEmpty() ? $sub_models->first()->unique_code : '');

            $device_categories_as_json = Category::with([])->where('unique_code', 'like', 'Q%')->pluck('name','unique_code')->toJson();
            $device_entire_models_by_category_unique_code_as_json = EntireModel::with([])->where('unique_code', 'like', 'Q%')->get()->groupBy('category_unique_code')->toJson();

            return view('Entire.Kind.index', [
                'categories' => $categories,
                'entire_models' => $entire_models,
                'sub_models' => $sub_models,
                'current_category_unique_code' => $current_category_unique_code,
                'current_entire_model_unique_code' => $current_entire_model_unique_code,
                'current_sub_model_unique_code' => $current_sub_model_unique_code,
                'device_categories_as_json' => $device_categories_as_json,
                'device_entire_models_by_category_unique_code_as_json' => $device_entire_models_by_category_unique_code_as_json,
            ]);
        } catch (FuncNotFoundException $e) {
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
           \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 获取种类
     * @param string $unique_code
     */
    final public function getCategory(string $unique_code)
    {
        try {
            $category = Category::with([])->where('unique_code', $unique_code)->firstOrFail();

            return JsonResponseFacade::data(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('种类不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加种类
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postCategory(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('种类名称不能为空');
            Category::with([])->where('name', $request->get('name'))->firstOrFail();
            return JsonResponseFacade::errorForbidden('种类名称重复');
        } catch (ModelNotFoundException $e) {
            DB::beginTransaction();
            $races = ['S' => 0, 'Q' => 1];

            $max_category = Category::with([])->orderByDesc('id')->where('unique_code', 'like', $request->get('race') . '%')->first();
            if (!$max_category) {
                $new_unique_code = $request->get('race') . '01';
            } else {
                $max_category_unique_code = intval(substr($max_category->unique_code, 1, 2));
                $new_unique_code = $request->get('race') . str_pad(++$max_category_unique_code, 2, '0', 0);
            }

            // 写入数据库
            $category = Category::with([])->create([
                'name' => $request->get('name'),
                'unique_code' => $new_unique_code,
                'race_unique_code' => $races[$request->get('race')],
            ]);

            DB::commit();
            return JsonResponseFacade::created(['category' => $category], '添加成功');
        } catch (\Throwable $e) {
            DB::rollback();
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑种类
     * @param Request $request
     * @param string $unique_code
     */
    final public function putCategory(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('种类名称不能为空');

            $repeat = Category::with([])->where('name', $request->get('name'))->where('unique_code', '<>', $request->get('unique_code'))->first();
            if ($repeat) return JsonResponseFacade::errorForbidden('种类名称被占用');

            $category = Category::with([])->where('unique_code', $request->get('unique_code'))->firstOrFail();
            $category->fill(['name' => $request->get('name')])->saveOrFail();

        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取类型
     * @param string $unique_code
     */
    final public function getEntireModel(string $unique_code)
    {
        try {
            $entire_model = EntireModel::with([])->where('unique_code', $unique_code)->firstOrFail();

            return JsonResponseFacade::data(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('类型不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加类型
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postEntireModel(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('类型名称不能为空');
            $entire_model = EntireModel::with(['Category'])->where('category_unique_code', $request->get('category_unique_code'))->where('name', $request->get('name'))->firstOrFail();

            return JsonResponseFacade::errorForbidden("类型：{$request->get('name')}({$entire_model->Category->name})名称重复");
        } catch (ModelNotFoundException $e) {
            DB::beginTransaction();

            $max_entire_model = EntireModel::with([])->orderByDesc('id')->where('category_unique_code', $request->get('category_unique_code'))->first();
            if (!$max_entire_model) {
                $new_unique_code = $request->get('category_unique_code') . '01';
            } else {
                $max_entire_model_unique_code = intval(substr($max_entire_model->unique_code, 2, 2));
                $new_unique_code = $request->get('category_unique_code') . str_pad(++$max_entire_model_unique_code, 2, '0', 0);
            }

            // 写入数据库
            $entire_model = EntireModel::with([])->create([
                'name' => $request->get('name'),
                'unique_code' => $new_unique_code,
                'category_unique_code' => $request->get('category_unique_code'),
                'fix_cycle_unit' => 'YEAR',
                'fix_cycle_value' => intval($request->get('fix_cycle_value', 0)),
                'is_sub_model' => false,
            ]);

            DB::commit();

            return JsonResponseFacade::created(['entire_model' => $entire_model], '添加成功');
        } catch (\Throwable $e) {
            DB::rollback();
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑类型
     * @param Request $request
     * @param string $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function putEntireModel(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('类型名称不能为空');

            $repeat = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $request->get('category_unique_code'))->where('name', $request->get('name'))->where('unique_code', '<>', $request->get('unique_code'))->first();
            if ($repeat) return JsonResponseFacade::errorForbidden('类型名称被占用');

            $entire_model = EntireModel::with([])->where('unique_code', $request->get('unique_code'))->firstOrFail();
            $entire_model->fill(['name' => $request->get('name'), 'fix_cycle_value' => $request->get('fix_cycle_value', 0)])->saveOrFail();

            return JsonResponseFacade::updated(['entire_model' => $entire_model], '编辑成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('类型不存在');
        } catch (\Throwable $e) {
            return response()->json(['msg' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 获取子类
     * @param string $unique_code
     * @return mixed
     */
    final public function getSubModel(string $unique_code)
    {
        try {
            $sub_model = EntireModel::with([])->where('unique_code', $unique_code)->firstOrFail();

            return JsonResponseFacade::data(['sub_model' => $sub_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('型号不存在');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 新建型号
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postSubModel(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('型号名称不能为空');
            $sub_model = EntireModel::with(['Category', 'Parent'])->where('category_unique_code', $request->get('category_unique_code'))->where('parent_unique_code', $request->get('entire_model_unique_code'))->where('name', $request->get('name'))->firstOrFail();

            return JsonResponseFacade::errorForbidden("型号：{$request->get('name')}({$sub_model->Category->name} > {$sub_model->Parent->name})名称重复");
        } catch (ModelNotFoundException $e) {
            DB::beginTransaction();

            $max_sub_model = EntireModel::with([])->orderByDesc('id')->where('category_unique_code', $request->get('category_unique_code'))->where('parent_unique_code', $request->get('entire_model_unique_code'))->first();
            if (!$max_sub_model) {
                $new_unique_code = $request->get('entire_model_unique_code') . '01';
            } else {
                $max_sub_model_unique_code = intval(TextFacade::from36(Str::substr($max_sub_model->unique_code, 5, 3)));
                $new_unique_code = $request->get('entire_model_unique_code') . str_pad(TextFacade::to36(++$max_sub_model_unique_code), 2, '0', 0);
            }

            // 写入数据库
            $sub_model = EntireModel::with([])->create([
                'name' => $request->get('name'),
                'unique_code' => $new_unique_code,
                'category_unique_code' => $request->get('category_unique_code'),
                'fix_cycle_unit' => 'YEAR',
                'fix_cycle_value' => intval($request->get('fix_cycle_value', 0)),
                'is_sub_model' => true,
                'parent_unique_code' => $request->get('entire_model_unique_code'),
            ]);

            DB::commit();

            return JsonResponseFacade::created(['sub_model' => $sub_model], '添加成功');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function putSubModel(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('型号名称不能为空');

            $repeat = EntireModel::with([])->where('is_sub_model', true)->where('name', $request->get('name'))->where('unique_code', '<>', $request->get('unique_code'))->first();
            if ($repeat) return JsonResponseFacade::errorForbidden('型号名称被占用');

            $sub_model = EntireModel::with([])->where('unique_code', $request->get('unique_code'))->firstOrFail();
            $sub_model->fill(['name' => $request->get('name'), 'fix_cycle_value' => $request->get('fix_cycle_value', 0)])->saveOrFail();

            return JsonResponseFacade::updated(['sub_model' => $sub_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('没有找到型号');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
