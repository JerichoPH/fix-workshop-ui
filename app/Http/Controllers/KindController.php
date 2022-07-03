<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class KindController extends Controller
{
    /**
     * 根据唯一编号组获取种类型名称
     * @return mixed
     */
    final public function getByIdentityCodes()
    {
        try {
            $categories = [];
            $entire_models = [];
            $sub_models = [];

            EntireInstance::with([])
                ->whereIn('identity_code', request('identity_codes'))
                ->groupBy(['entire_model_unique_code'])
                ->each(function ($entire_instance) use (&$categories, &$entire_models, &$sub_models) {
                    $category = @$entire_instance->Category ?: null;
                    $entire_model = @$entire_instance->EntireModel->Parent ?: @$entire_instance->EntireModel;
                    $sub_model = @$entire_instance->EntireModel->Parent ? @$entire_instance->EntireModel : null;

                    if (empty($category->nickname)) $category->fill(['nickname' => $category->name])->saveOrFail();
                    if (empty($entire_model->nickname)) $entire_model->fill(['nickname' => $entire_model->name])->saveOrFail();
                    if ($sub_model) {
                        if (empty($sub_model->nickname)) $sub_model->fill(['nickname' => $sub_model->name])->saveOrFail();
                    }

                    if(request("size_type")!=10){
                        if (Str::length($category->nickname) > env("NICKNAME_LENGTH_CATEGORY", 8)) $categories[$category->unique_code] = $category;
                        if (Str::length($entire_model->nickname) > env("NICKNAME_LENGTH_ENTIRE_MODEL", 8)) $entire_models[$entire_model->unique_code] = $entire_model;
                        if ($sub_model) {
                            if (Str::length($sub_model->nickname) > env("NICKNAME_LENGTH_SUB_MODEL", 14)) $sub_models[$sub_model->unique_code] = $sub_model;
                        }
                    }
                });

            return JsonResponseFacade::dict(['kinds' => [
                'categories' => array_values($categories),
                'entire_models' => array_values($entire_models),
                'sub_models' => array_values($sub_models),
            ]]);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 修改种类别名
     * @param Request $request
     * @param string $unique_code
     * @return mixed
     */
    final public function putCategoryNickname(Request $request, string $unique_code)
    {
        try {
            $category = Category::with([])->where('unique_code', $unique_code)->firstOrFail();
            $category->nickname = $request->get('nickname');
            $category->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('种类不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 修改类型别名
     * @param Request $request
     * @param string $unique_code
     */
    final public function putEntireModelNickname(Request $request, string $unique_code)
    {
        try {
            $entire_model = EntireModel::with([])->where('unique_code', $unique_code)->firstOrFail();
            $entire_model->nickname = $request->get('nickname');
            $entire_model->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 修改型号别名
     * @param Request $request
     * @param string $unique_code
     */
    final public function putSubModelNickname(Request $request, string $unique_code)
    {
        try {
            $sub_model = EntireModel::with([])->where('unique_code', $unique_code)->firstOrFail();
            $sub_model->nickname = $request->get('nickname');
            $sub_model->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量编辑种类型昵称
     */
    final public function putNicknames(Request $request)
    {
        try {
            DB::beginTransaction();

            foreach ($request->all() as $unique_code => $nickname) {
                if (empty($nickname)) {
                    return JsonResponseFacade::errorValidate('别名不能为空');
                }

                if (strlen($unique_code) == 3) {
                    $category = Category::with([])->where('unique_code', $unique_code)->first();
                    if (!$category) return JsonResponseFacade::errorEmpty("没有找到种类：{$unique_code}");

                    $category->nickname = $nickname;
                    $category->saveOrFail();
                } else if (strlen($unique_code) > 3) {
                    $entire_model = EntireModel::with([])->where('unique_code', $unique_code)->first();
                    if (!$entire_model) return JsonResponseFacade::errorEmpty("没有找到型号：{$unique_code}");

                    $entire_model->nickname = $nickname;
                    $entire_model->saveOrFail();
                } else {
                    return JsonResponseFacade::errorValidate('编码类型错误：{$unique_code}');
                }
            }

            DB::commit();
            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            DB::rollBack();
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
