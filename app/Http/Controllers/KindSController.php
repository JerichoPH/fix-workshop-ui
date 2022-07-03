<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\Category;
use App\Model\EntireModel;
use App\Model\PartCategory;
use App\Model\PartModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class KindSController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        // 获取器材种类
        $categories = Category::with([])->where("is_show", true)->where('unique_code', 'like', 'S%')->get();
        $current_category_unique_code = request('category_unique_code', $categories->isNotEmpty() ? $categories->first()->unique_code : '');
        // 获取器材类型
        $entire_models = EntireModel::with(['EntireModelImages',])->withCount('EntireModelImages')->where('category_unique_code', $current_category_unique_code)->where('is_sub_model', false)->get();
        $current_entire_model_unique_code = request('entire_model_unique_code', $entire_models->isNotEmpty() ? $entire_models->first()->unique_code : '');
        // 获取器材型号
        $part_models = PartModel::with(['PartCategory', 'PartCategory.EntireModel', 'PartModelImages',])->withCount('PartModelImages')->where('entire_model_unique_code', $current_entire_model_unique_code)->get();
        $current_part_model_unique_code = request('part_model_unique_code', $part_models->isNotEmpty() ? $part_models->first()->unique_code : '');
        // 获取部件种类
        $part_categories = PartCategory::with([])->where('category_unique_code', $current_category_unique_code)->get();
        if (request()->ajax()) {
            try {
                return JsonResponseFacade::data([
                    'categories' => $categories,
                    'entire_models' => $entire_models,
                    'part_models' => $part_models,
                    'part_categories' => $part_categories,
                    'current_category_unique_code' => $current_category_unique_code,
                    'current_entire_model_unique_code' => $current_entire_model_unique_code,
                    'current_part_model_unique_code' => $current_part_model_unique_code,
                ]);
            } catch (\Throwable $e) {
                return JsonResponseFacade::errorException($e);
            }
        } else {
            try {
                return view('KindS.index', [
                    'categories_as_json' => $categories->toJson(),
                    'entire_models_as_json' => $entire_models->groupBy(['category_unique_code'])->toJson(),
                    'part_models_as_json' => $part_models->groupBy(['entire_model_unique_code'])->toJson(),
                    'part_categories_as_json' => $part_categories->groupBy(['category_unique_code'])->toJson(),
                    'entire_models' => $entire_models,
                    'part_models' => $part_models,
                    'current_category_unique_code' => $current_category_unique_code,
                    'current_entire_model_unique_code' => $current_entire_model_unique_code,
                    'current_part_model_unique_code' => $current_part_model_unique_code,
                ]);
            } catch (\Throwable $e) {
                return CommonFacade::ddExceptionWithAppDebug($e);
            }
        }
    }

    final public function getSearch()
    {
        try {
            $name = request('name', '') ?? '';
            if (!$name) return back()->with('danger', '名称不能为空');

            switch (request('search_type')) {
                case 'entire_model':
                    // 按类型搜索
                    $data = EntireModel::with(['Category',])
                        ->whereHas('Category')
                        ->where('is_sub_model', false)
                        ->where('name', 'like', "%{$name}%")
                        ->get();
                    if ($data->count() === 1) {
                        $queries = http_build_query([
                            'category_unique_code' => $data->first()->Category->unique_code,
                            'entire_model_unique_code' => $data->first()->unique_code,
                            'search_type' => request('search_type'),
                            'name' => request('name'),
                        ]);
                        return redirect("/kindS?{$queries}");
                    } else {
                        return view('KindS.search', ['data' => $data,]);
                    }
                case 'model':
                    // 按型号搜索
                    $data = PartModel::with(['Category', 'Parent',])
                        ->whereHas('Category')
                        ->whereHas('Parent')
                        ->where('name', 'like', "%{$name}%")
                        ->get();
                    if ($data->count() === 1) {
                        $queries = http_build_query([
                            'category_unique_code' => $data->first()->Category->unique_code,
                            'entire_model_unique_code' => $data->first()->Parent->unique_code,
                            'model_unique_code' => $data->first()->unique_code,
                            'search_type' => request('search_type'),
                            'name' => request('name'),
                        ]);
                        return redirect("/kindS?{$queries}");
                    } else {
                        return view('KindS.search', ['data' => $data,]);
                    }
                default:
                    return back()->with('danger', '搜索类型错误');
            }
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 添加种类
     * @param Request $request
     * @return JsonResponse
     */
    final public function postCategory(Request $request): JsonResponse
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_category = Category::with([])->where('name', $request->get('name'))->first();
            if ($repeat_category) return JsonResponseFacade::errorForbidden('名称重复');

            $category = Category::with([])->create([
                'unique_code' => Category::generateUniqueCode('S'),
                'name' => $request->get('name'),
            ]);

            return JsonResponseFacade::created(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取种类
     * @param string $unique_code
     */
    final public function getCategory(string $unique_code)
    {
        try {
            $category = Category::with([])->where('unique_code', $unique_code)->first();

            return JsonResponseFacade::data(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑种类
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function putCategory(Request $request): JsonResponse
    {
        try {
            $category = Category::with([])->where('unique_code', $request->get('unique_code'))->first();

            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_category = Category::with([])->where('id', '<>', $category->id)->where('name', $request->get('name'))->first();
            if ($repeat_category) return JsonResponseFacade::errorForbidden('名称重复');

            $category->fill(['name' => $request->get('name')])->saveOrFail();

            return JsonResponseFacade::updated(['category' => $category]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取类型数据
     * @param string $unique_code
     */
    final public function getEntireModel(string $unique_code)
    {
        try {
            $entire_model = EntireModel::with([])
                ->where('is_sub_model', false)
                ->where('unique_code', $unique_code)
                ->first();

            return JsonResponseFacade::data(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加类型
     * @param Request $request
     * @return JsonResponse
     */
    final public function postEntireModel(Request $request): JsonResponse
    {
        try {
            if (!$request->get('category_unique_code')) return JsonResponseFacade::errorEmpty('种类代码参数丢失');
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_entire_model = EntireModel::with([])
                ->where('category_unique_code', $request->get('category_unique_code'))
                ->where('is_sub_model', false)->where('name', $request->get('name'))
                ->first();
            if ($repeat_entire_model) return JsonResponseFacade::errorForbidden('名称重复');

            if ($request->get('fix_cycle_value') < 0) return JsonResponseFacade::errorForbidden('周期修年必须大于0');

            $entire_model = EntireModel::with([])->create([
                'is_sub_model' => false,
                'unique_code' => EntireModel::generateEntireModelUniqueCode($request->get('category_unique_code')),
                'name' => $request->get('name'),
                'category_unique_code' => $request->get('category_unique_code'),
                'fix_cycle_value' => $request->get('fix_cycle_value', 0),
            ]);

            return JsonResponseFacade::created(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑类型
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function putEntireModel(Request $request): JsonResponse
    {
        try {
            $entire_model = EntireModel::with([])->where('unique_code', $request->get('unique_code'))->where('is_sub_model', false)->first();

            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $repeat_entire_model = EntireModel::with([])->where('id', '<>', $entire_model->id)->where('name', $request->get('name'))->first();
            if ($repeat_entire_model) return JsonResponseFacade::errorForbidden('名称重复');

            if ($request->get('fix_cycle_value') < 0) return JsonResponseFacade::errorForbidden('周期修年必须大于0');

            $entire_model->fill(['name' => $request->get('name'), 'fix_cycle_value' => $request->get('fix_cycle_value', 0)])->saveOrFail();

            return JsonResponseFacade::updated(['entire_model' => $entire_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取型号数据
     * @param string $unique_code
     */
    final public function getPartModel(string $unique_code)
    {
        try {
            $part_model = PartModel::with([])->where('unique_code', $unique_code)->firstOrFail();

            return JsonResponseFacade::data(['part_model' => $part_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 添加型号
     * @param Request $request
     * @return JsonResponse
     */
    final public function postPartModel(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            if (!$request->get('part_category_id')) return JsonResponseFacade::errorEmpty('请选择部件种类');
            $repeat_part_model = PartModel::with([])
                ->where('entire_model_unique_code', $request->get('entire_model_unique_code'))
                ->where('name', $request->get('name'))
                ->first();
            if ($repeat_part_model) return JsonResponseFacade::errorForbidden('名称重复');

            $part_model = PartModel::with([])->create([
                'name' => $request->get('name'),
                'unique_code' => PartModel::generateUniqueCode($request->get('entire_model_unique_code')),
                'category_unique_code' => substr($request->get('entire_model_unique_code'), 0, 3),
                'entire_model_unique_code' => $request->get('entire_model_unique_code'),
                'part_category_id' => $request->get('part_category_id'),
            ]);

            return JsonResponseFacade::created(['part_model' => $part_model,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 编辑部件型号
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function putPartModel(Request $request)
    {
        try {
            if (!$request->get('name')) return JsonResponseFacade::errorEmpty('名称不能为空');
            $part_model = PartModel::with([])->where('unique_code', $request->get('unique_code'))->firstOrFail();
            $repeat_part_model = PartModel::with([])
                ->where('id', '<>', $part_model->id)
                ->where('name', $request->get('name'))
                ->first();
            if ($repeat_part_model) return JsonResponseFacade::errorForbidden('名称重复');

            $part_model = $part_model->fill(['name' => $request->get('name'),])->saveOrFail();

            return JsonResponseFacade::created(['part_model' => $part_model]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

}
