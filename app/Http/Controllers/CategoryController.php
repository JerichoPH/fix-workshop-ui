<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Http\Requests\V1\CategoryRequest;
use App\Model\Category;
use App\Model\EntireModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Jericho\BadRequestException;
use Jericho\CurlHelper;
use Jericho\HttpResponseHelper;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $categories = Category::orderByDesc('id')->paginate();
        return view('Category.index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $v = Validator::make($request->all(), CategoryRequest::$RULES, CategoryRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            $category = new Category;
            $category->fill($request->all())->saveOrFail();

            return Response::make('新建成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $categoryUniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show(string $categoryUniqueCode)
    {
        try {
            Session::put('currentCategoryUniqueCode', $categoryUniqueCode);
            $category = Category::where('unique_code', $categoryUniqueCode)->firstOrFail(['name', 'unique_code']);
            switch (substr($categoryUniqueCode, 0, 1)) {
                case 'Q':
                    # 关键器件
                    $entireModels = EntireModel::with(['Category'])->where('category_unique_code', $categoryUniqueCode)->where('is_sub_model', 1);
                    break;
                case 'S':
                default:
                    # 设备单元
                    $entireModels = EntireModel::with(['Category'])->where('category_unique_code', $categoryUniqueCode)->where('is_sub_model', 0);
                    break;
            }
            if (request()->ajax()) return Response::json($entireModels->get());
            return view('Category.show')
                ->with('entireModels', $entireModels->paginate())
                ->with('category', $category);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $category = Category::findOrFail($id);
            return view('Category.edit', ['category' => $category]);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $v = Validator::make($request->all(), CategoryRequest::$RULES, CategoryRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            $category = Category::findOrFail($id);
            $category->fill($request->all())->saveOrFail();

            return Response::make('编辑成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            if (!$category->trashed()) return Response::make('删除失败', 500);

            return Response::make('删除成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 从数据中台备份到本地
     */
    final public function getBackupFromSPAS()
    {
        try {
            # 同步种类
            $categories_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($categories_response['code'] != 200) return response()->json($categories_response['body'], $categories_response['code']);

            # 同步类型
            $entire_models_response = CurlHelper::init([
                'url' => "{$this->_root_url}/basic/entireModel",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($entire_models_response['code'] != 200) return response()->json($entire_models_response['body'], $entire_models_response['code']);

            # 同步部件型号
            $part_models_response = CurlHelper::init([
                'url' => "{$this->_root_url}/basic/partModel",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($part_models_response['code'] != 200) return response()->json($part_models_response['body'], $part_models_response['code']);

            # 同步子类
            $sub_models_response = CurlHelper::init([
                'url' => "{$this->_root_url}/basic/subModel",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($sub_models_response['code'] != 200) return response()->json($sub_models_response['body'], $sub_models_response['code']);

            # 写入种类
            $insert_categories = [];
            foreach ($categories_response['body']['data'] as $datum) {
                $insert_categories[] = [
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'race_unique_code' => $datum['race'],
                ];
            }
            if ($insert_categories) {
                DB::table('basic_categories')->truncate();
                DB::table('basic_categories')->insert($insert_categories);
            }

            # 写入类型
            $insert_entire_models = [];
            foreach ($entire_models_response['body']['data'] as $datum) {
                $insert_entire_models[] = [
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'category_unique_code' => $datum['category'],
                ];
            }
            if ($insert_entire_models) {
                DB::table('basic_entire_models')->truncate();
                DB::table('basic_entire_models')->insert($insert_entire_models);
            }

            # 写入部件类型
            $insert_part_models = [];
            foreach ($part_models_response['body']['data'] as $datum) {
                $insert_part_models[] = [
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'entire_model_unique_code' => $datum['entire_model'],
                ];
            }
            if ($insert_part_models) {
                DB::table('basic_part_models')->truncate();
                DB::table('basic_part_models')->insert($insert_part_models);
            }

            # 写入子类
            $insert_sub_models = [];
            foreach ($sub_models_response['body']['data'] as $datum) {
                $insert_sub_models[] = [
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'entire_model_unique_code' => $datum['entire_model'],
                ];
            }
            if ($insert_sub_models) {
                DB::table('basic_sub_models')->truncate();
                DB::table('basic_sub_models')->insert($insert_sub_models);
            }

            return response()->json(['message' => '同步成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 获取种类
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubModelWithCategory(Request $request)
    {
        try {
            $category_unique_code = $request->get('category_unique_code', '');
            $entire_models = DB::table('entire_models')
                ->select('id', 'name', 'unique_code')
                ->where('deleted_at', null)
                ->where('is_sub_model', true)
                ->when(
                    !empty($category_unique_code),
                    function ($query) use ($category_unique_code) {
                        return $query->where('category_unique_code', $category_unique_code);
                    }
                )
                ->get()
                ->toArray();
            $part_models = DB::table('part_models')
                ->where('deleted_at', null)
                ->when(
                    !empty($category_unique_code),
                    function ($query) use ($category_unique_code) {
                        return $query->where('category_unique_code', $category_unique_code);
                    }
                )
                ->get()
                ->toArray();
            return HttpResponseHelper::data(array_merge($entire_models, $part_models));
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 获取类型
     * @param Request $request
     * @param string $categoryUniqueCode
     * @return JsonResponse
     */
    final public function getEntireModel(Request $request, string $categoryUniqueCode): JsonResponse
    {
        try {
            $entireModels = DB::table('entire_models')
                ->where('deleted_at', null)
                ->where('category_unique_code', $categoryUniqueCode)
                ->where('is_sub_model', false)
                ->pluck('name', 'unique_code')->toArray();
            return JsonResponseFacade::data($entireModels);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取型号
     * @param Request $request
     * @param string $entireModelUniqueCode
     * @return JsonResponse
     */
    final public function getSubModel(Request $request, string $entireModelUniqueCode): JsonResponse
    {
        try {
            $subModels = array_merge(
                DB::table('entire_models')
                    ->where('deleted_at', null)
                    ->where('parent_unique_code', $entireModelUniqueCode)
                    ->where('is_sub_model', true)
                    ->pluck('name', 'unique_code')
                    ->toArray(),
                DB::table('part_models')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModelUniqueCode)
                    ->pluck('name', 'unique_code')
                    ->toArray()
            );

            return JsonResponseFacade::data($subModels);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

}
