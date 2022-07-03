<?php

namespace App\Http\Controllers\Entire;

use App\Facades\ExcelReader;
use App\Facades\PivotEntireModelAndFactoryFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntireModelStoreRequest;
use App\Http\Requests\EntireModelUpdateRequest;
use App\Model\Category;
use App\Model\EntireModel;
use App\Model\PivotEntireModelAndPartModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\ValidateHelper;

class ModelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return EntireModel[]|\Illuminate\Contracts\View\Factory|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            if (request()->ajax())
                return EntireModel::with([])
                    ->where(\request('type'), \request(request('type')))
                    ->where('is_sub_model', false)
                    ->get();

            if (request('download') == 1) {
                $sub_models = DB::table('entire_models as sm')
                    ->select([
                        'sm.name as sm_name',
                        'sm.unique_code as sm_unique_code',
                        'em.name as em_name',
                        'em.unique_code as em_unique_code',
                        'c.name as c_name',
                        'c.unique_code as c_unique_code',
                    ])
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->where('sm.deleted_at', null)
                    ->get();

                ExcelWriteHelper::download(function ($excel) use ($sub_models) {
                    $excel->setActiveSheetIndex(0);
                    $sheet = $excel->getActiveSheet();

                    $sheet->setCellValue('A1', '种类名称');
                    $sheet->setCellValue('B1', '种类代码');
                    $sheet->setCellValue('C1', '类型名称');
                    $sheet->setCellValue('D1', '类型代码');
                    $sheet->setCellValue('E1', '子类名称');
                    $sheet->setCellValue('F1', '子类代码');

                    $row = 1;
                    foreach ($sub_models as $sub_model) {
                        $row++;
                        $sheet->setCellValue("A{$row}", $sub_model->c_name);
                        $sheet->setCellValue("B{$row}", $sub_model->c_unique_code);
                        $sheet->setCellValue("C{$row}", $sub_model->em_name);
                        $sheet->setCellValue("D{$row}", $sub_model->em_unique_code);
                        $sheet->setCellValue("E{$row}", $sub_model->sm_name);
                        $sheet->setCellValue("F{$row}", $sub_model->sm_unique_code);
                    }

                    return $excel;
                }, '子类');
            }

            $entireModels = EntireModel::with(['Category'])
                ->where('is_sub_model', false)
                ->orderByDesc('id')
                ->when(request('unique_code'), function ($query) {
                    $query->where('unique_code', 'like', request('unique_code') . '%');
                })
                ->when(request('name'), function ($query) {
                    $query->where('name', 'like', request('name') . '%');
                })
                ->paginate();
            return view('Entire.Model.index', ['entireModels' => $entireModels]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('')->with('danger', '意外错误');
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function create()
    {
        $categories = Category::orderByDesc('id')->pluck('name', 'unique_code')->toJson();
        return view('Entire.Model.create', [
            'categoriesAsJson' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new EntireModelStoreRequest());
            if ($v !== true) return Response::make($v, 422);
            DB::transaction(function () use ($request) {
                # 保存整件类型
                $entireModel = new EntireModel;
                $entireModel->fill($request->all())->saveOrFail();

                # 保存整件类型与部件型号
                if ($request->get("part_model_unique_code")) {
                    $partModels = [];
                    foreach ($request->get('part_model_unique_code') as $item) {
                        $partModels[] = [
                            'entire_model_unique_code' => $request->get('unique_code'),
                            'part_model_unique_code' => $item,
                        ];
                    }
                    if (!DB::table('pivot_entire_model_and_part_models')->insert($partModels)) throw new \Exception('保存对应关系失败');
                }
            });

            return Response::make('新建成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make($exceptionMessage, 500);
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
     *
     * @param $uniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($uniqueCode)
    {
        try {
            $entireModel = EntireModel::with([])->where('unique_code', $uniqueCode)->firstOrFail();
            $partModels = PivotEntireModelAndPartModel::with([])->where('entire_model_unique_code', $entireModel->unique_code)->pluck('part_model_unique_code');
            $categories = Category::with([])->orderByDesc('id')->pluck('name', 'unique_code');
            $boundExtraTags = DB::table('pivot_entire_model_and_extra_tags')->where('entire_model_unique_code', $entireModel->unique_code)->pluck('extra_tag');
            $boundFactories = DB::table('pivot_entire_model_and_factories')->where('entire_model_unique_code', $entireModel->unique_code)->get(['entire_model_unique_code', 'factory_name as name']);

            return view('Entire.Model.edit', [
                'boundFactories' => $boundFactories,
                'boundExtraTags' => $boundExtraTags,
                'entireModel' => $entireModel,
                'categories' => $categories,
                'partModels' => $partModels,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return back()->with('danger', '意外错误' . $exceptionMessage);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function update(Request $request, $id)
    {
        try {
            $_POST['id'] = $id;
            $v = ValidateHelper::firstErrorByRequest($request, new EntireModelUpdateRequest());
            if ($v !== true) return Response::make($v, 422);

            DB::transaction(function () use ($request, $id) {
                # 保存整件型号
                $entireModel = EntireModel::with([])->findOrFail($id);
                $entireModel->fill($request->except('extra_tag', 'factory_name', 'part_model_unique_code'))->saveOrFail();

                # 修改该型号下所有设备周期修时间和单位
                DB::table('entire_instances')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModel->unique_code)
                    ->update([
                        'fix_cycle_unit' => $request->get('fix_cycle_unit', 'YEAR'),
                        'fix_cycle_value' => $request->get('fix_cycle_value', 0),
                    ]);

                if ($request->has('part_model_unique_code')) {
                    # 清除所有关联关系
                    DB::table('pivot_entire_model_and_part_models')->where('entire_model_unique_code', $entireModel->unique_code)->delete();
                    # 重建关联关系
                    $partModels = [];
                    foreach ($request->get('part_model_unique_code') as $item) {
                        $partModels[] = [
                            'category_unique_code' => $entireModel->category_unique_code,
                            'entire_model_unique_code' => $entireModel->unique_code,
                            'part_model_unique_code' => $item,
                        ];
                    }
                    if (!DB::table('pivot_entire_model_and_part_models')->insert($partModels)) throw new \Exception('保存对应关系失败');
                }
            });

            return response()->make('编辑成功');
        } catch (ModelNotFoundException $exception) {
            return response()->make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return response()->make("{$exceptionMessage}\r\n{$exceptionFile}\r\n{$exceptionLine}", 500);
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
        try {
            $entireModel = EntireModel::findOrFail($id);
            $entireModel->delete();
            if (!$entireModel->trashed()) return Response::make('删除失败', 500);

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
     * 批量导入页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getBatch()
    {
        return view('Entire.Model.batch');
    }

    /**
     * 批量导入
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    final public function postBatch(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) return back()->with('只能上传excel');
            $currentDate = date('Y-m-d H:i:s');
            $successList = [];
            $nullList = [];
            $excel = DB::transaction(function () use ($request) {
                $excel = ExcelReader::init($request, 'file')->readSheetByName('整件', 2, 0, function ($row) {
                    list($categoryName, $categoryUniqueCode, $entireModelName, $entireModelUniqueCode, $subEntireModelName, $fixCycleValue, $subEntireModelUniqueCode, $factoryName) = $row;

                    # 如果不存在类型，则添加新类型
                    $entireModel = DB::table('entire_models')->where('unique_code', $entireModelUniqueCode)->first();
                    if (!$entireModel) {
                        $entireModel = new EntireModel();
                        $entireModel
                            ->fill([
                                'name' => $entireModelName,
                                'unique_code' => $entireModelUniqueCode,
                                'category_unique_code' => $categoryUniqueCode,
                                'fix_cycle_value' => $fixCycleValue,
                                'is_sub_model' => 0,
                            ])
                            ->saveOrFail();
                    }

                    if ($subEntireModelUniqueCode) {
                        # 如果不存在子类，则添加新子类
                        $subEntireModel = DB::table('entire_models')->where('unique_code', $subEntireModelUniqueCode)->where('is_sub_model', 1)->first();
                        if (!$subEntireModel) {
                            $subEntireModel = new EntireModel();
                            $subEntireModel
                                ->fill([
                                    'name' => $subEntireModelName,
                                    'unique_code' => $subEntireModelUniqueCode,
                                    'category_unique_code' => $categoryUniqueCode,
                                    'parent_unique_code' => $entireModelUniqueCode,
                                    'fix_cycle_value' => $fixCycleValue,
                                    'is_sub_model' => 1,
                                ])
                                ->saveOrFail();
                        }
                    }
                    return $entireModelUniqueCode;
                });

                return $excel;
            });

            DB::transaction(function () use ($request, $currentDate, $nullList, $excel) {
                foreach ($excel['success'] as $item) {
                    # 如果存在检测模板的话，则连同模板一起修改
                    DB::table('measurements')->where('entire_model_unique_code', $item)->update(['entire_model_unique_code' => $item]);
                }
            });

            return back()->with('success', '上传成功');
        } catch (ModelNotFoundException $exception) {
            return $request->ajax() ? response()->make(env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在', 404) : back()->withInput()->with('danger', env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return $request->ajax() ? response()->make(env('APP_DEBUG') ? $eMsg : "意外错误", 500) : back()->withInput()->with('danger', env('APP_DEBUG') ? $eMsg : "意外错误");
        }
    }

    /**
     * 批量导入整件类型与供应商对应关系页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getBatchFactory()
    {
        return view($this->view('batchFactory'));
    }

    final public function view($viewName)
    {
        return "Entire.Model.{$viewName}";
    }

    /**
     * 批量导入整件类型与供应商对应关系
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    final public function postBatchFactory(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) return back()->with('只能上传excel');
            $excel = PivotEntireModelAndFactoryFacade::batch($request, 'file');
            DB::table('pivot_entire_model_and_factories')->insert($excel['success']);

            return $request->ajax() ?
                response()->make('导入成功') :
                back()->with('success', '导入成功');
        } catch (ModelNotFoundException $exception) {
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    '数据不存在：' . $exception->getMessage() :
                    '数据不存在', 404) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    '数据不存在：' . $exception->getMessage() :
                    '数据不存在');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "意外错误", 500) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "意外错误");
        }
    }

    /**
     * 根据种类代码获取类型
     * @param string $categoryUniqueCode
     * @return string
     */
    final public function getEntireModelByCategoryUniqueCode(string $categoryUniqueCode)
    {
        return DB::table('entire_models')
            ->where('deleted_at', null)
            ->where('category_unique_code', $categoryUniqueCode)
            ->where('is_sub_model', false)
            ->pluck('name', 'unique_code');
    }

    /**
     * 根据类型获取型号或子类
     * @param string $entireModelUniqueCode
     * @return string
     */
    final public function getSubModelByEntireModel(string $entireModelUniqueCode)
    {
        switch (substr($entireModelUniqueCode, 0, 1)) {
            case 'S':
                return DB::table('part_models')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModelUniqueCode)
                    ->pluck('name', 'unique_code');
                break;
            case 'Q':
                return DB::table('entire_models')
                    ->where('deleted_at', null)
                    ->where('part_model_unique_code', $entireModelUniqueCode)
                    ->where('is_sub_model', true)
                    ->pluck('name', 'unique_code');
                break;
            default:
                return "参数错误";
                break;
        }
    }

    /**
     * 获取下一个型号代码
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getNextEntireModelUniqueCode(string $categoryUniqueCode)
    {
        $maxEntireModelUniqueCode = EntireModel::with([])->where('category_unique_code', $categoryUniqueCode)->where('is_sub_model', false)->max('unique_code');
        $uniqueCode = str_replace($categoryUniqueCode, '', $maxEntireModelUniqueCode);
        $nextEntireModelUniqueCode = $categoryUniqueCode . ($uniqueCode ? str_pad(strval(intval($uniqueCode) + 1), 2, "0", STR_PAD_LEFT) : "01");
        return response()->json(['message' => '读取成功', 'next' => $nextEntireModelUniqueCode, 'max' => $maxEntireModelUniqueCode]);
    }
}
