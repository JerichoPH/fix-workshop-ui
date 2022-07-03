<?php

namespace App\Http\Controllers\Entire;

use App\Facades\TextFacade;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\EntireModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubModelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $workAreas = ['无' => 0, '转辙机工区' => 1, '继电器工区' => 2];
            if (@$workAreas[session('account.work_area')] == 1) return redirect('entire/model');
            $categories = Category::with(['EntireModels'])
                ->when(session('account.work_area'), function ($query) use ($workAreas) {
                    switch (@$workAreas[session('account.work_area')]) {
                        case 0:
                            # 全部
                            return $query->where('unique_code', '<>', 'S03');
                        case 1:
                            # 转辙机
                            return $query;
                        case 2:
                            # 继电器
                            return $query->where('unique_code', 'Q01');
                        default:
                            # 综合
                            return $query->whereNotIn('unique_code', ['S03', 'Q01']);
                    }
                })
                ->has('EntireModels')
                ->get();

            $entireModels = EntireModel::with(['Category'])
                ->whereIn('category_unique_code', $categories->pluck('unique_code'))
                ->where('is_sub_model', false)
                ->get()
                ->groupBy('category.unique_code');

            $subModels = EntireModel::with(['Parent'])
                ->where('is_sub_model', true)
                ->when(request('category_unique_code'), function ($query) {
                    return $query->where('category_unique_code', request('category_unique_code'));
                })
                ->when(request('entire_model_unique_code'), function ($query) {
                    return $query->where('parent_unique_code', request('entire_model_unique_code'));
                })
                ->paginate();
            return view('Entire.SubModel.index', [
                'categoriesAsJson' => $categories->toJson(),
                'entireModelsAsJson' => $entireModels->toJson(),
                'subModels' => $subModels,
            ]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '异常错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $workAreas = ['无' => 0, '转辙机工区' => 1, '继电器工区' => 2];
            if (@$workAreas[session('account.work_area')] == 1) return redirect('entire/model');
            $categories = Category::with(['EntireModels'])
                ->when(session('account.work_area'), function ($query) use ($workAreas) {
                    switch (@$workAreas[session('account.work_area')]) {
                        case 0:
                            # 全部
                            return $query->where('unique_code', '<>', 'S03');
                        case 1:
                            # 转辙机
                            return $query;
                        case 2:
                            # 继电器
                            return $query->where('unique_code', 'Q01');
                        default:
                            # 综合
                            return $query->whereNotIn('unique_code', ['S03', 'Q01']);
                    }
                })
                ->has('EntireModels')
                ->get();

            $entireModels = EntireModel::with(['Category'])
                ->whereIn('category_unique_code', $categories->pluck('unique_code'))
                ->where('is_sub_model', false)
                ->get()
                ->groupBy('category.unique_code');

            return view('Entire.SubModel.create', [
                'categoriesAsJson' => $categories->toJson(),
                'entireModelsAsJson' => $entireModels->toJson(),
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '异常错误');
        }

    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $entireModel = EntireModel::with([])->create([
                'name' => $request->get('name'),
                'unique_code' => $request->get('unique_code'),
                'category_unique_code' => $request->get('category_unique_code'),
                'fix_cycle_value' => $request->get('fix_cycle_value'),
                'is_sub_model' => true,
                'parent_unique_code' => $request->get('entire_model_unique_code'),
            ]);
            return response()->json(['message' => '新建成功', 'data' => $entireModel]);
        } catch (\Exception $e) {
            return response()->json(['message' => '', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($id)
    {
        try {
            $subModel = EntireModel::with([
                'Category',
                'Parent',
            ])
                ->where('id', $id)
                ->first();

            return view('Entire.SubModel.edit', [
                'subModel' => $subModel,
            ]);
        } catch (\Exception $e) {
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function update(Request $request, $id)
    {
        try {
            # 修改型号
            $subModel = EntireModel::with([])->where('id', $id)->firstOrFail();
            $subModel->fill([
                'name' => $request->get('name'),
                'fix_cycle_value' => $request->get('fix_cycle_value'),
            ])->saveOrFail();

            # 修改设备
            DB::table('entire_instances')
                ->where('model_unique_code', $subModel->unique_code)
                ->update([
                    'model_name' => $request->get('name'),
                ]);

            return response()->json(['message' => '修改成功', 'data' => $subModel]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        //
    }

    /**
     * 获取下一个子类代码
     * @param string $entire_model_unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getNextUniqueCode(string $entire_model_unique_code)
    {
        try {
            $max_sub_model_unique_code = EntireModel::with([])->where('parent_unique_code', $entire_model_unique_code)->where('is_sub_model', true)->max('unique_code');
            $unique_code = str_replace($entire_model_unique_code, '', $max_sub_model_unique_code);
            $next_sub_model_unique_code = $entire_model_unique_code . ($unique_code ? str_pad(TextFacade::inc36($unique_code), 2, '0', 0) : '01');
            return response()->json([
                'message' => '读取成功',
                'unique_code' => $unique_code,
                'next' => $next_sub_model_unique_code,
                'max' => $max_sub_model_unique_code
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => '异常错误', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]]);
        }
    }
}
