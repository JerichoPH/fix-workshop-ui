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

                    $sheet->setCellValue('A1', '????????????');
                    $sheet->setCellValue('B1', '????????????');
                    $sheet->setCellValue('C1', '????????????');
                    $sheet->setCellValue('D1', '????????????');
                    $sheet->setCellValue('E1', '????????????');
                    $sheet->setCellValue('F1', '????????????');

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
                }, '??????');
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
            return redirect('')->with('danger', '????????????');
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
                # ??????????????????
                $entireModel = new EntireModel;
                $entireModel->fill($request->all())->saveOrFail();

                # ?????????????????????????????????
                if ($request->get("part_model_unique_code")) {
                    $partModels = [];
                    foreach ($request->get('part_model_unique_code') as $item) {
                        $partModels[] = [
                            'entire_model_unique_code' => $request->get('unique_code'),
                            'part_model_unique_code' => $item,
                        ];
                    }
                    if (!DB::table('pivot_entire_model_and_part_models')->insert($partModels)) throw new \Exception('????????????????????????');
                }
            });

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
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
            return back()->withInput()->with('danger', '???????????????');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return back()->with('danger', '????????????' . $exceptionMessage);
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
                # ??????????????????
                $entireModel = EntireModel::with([])->findOrFail($id);
                $entireModel->fill($request->except('extra_tag', 'factory_name', 'part_model_unique_code'))->saveOrFail();

                # ??????????????????????????????????????????????????????
                DB::table('entire_instances')
                    ->where('deleted_at', null)
                    ->where('entire_model_unique_code', $entireModel->unique_code)
                    ->update([
                        'fix_cycle_unit' => $request->get('fix_cycle_unit', 'YEAR'),
                        'fix_cycle_value' => $request->get('fix_cycle_value', 0),
                    ]);

                if ($request->has('part_model_unique_code')) {
                    # ????????????????????????
                    DB::table('pivot_entire_model_and_part_models')->where('entire_model_unique_code', $entireModel->unique_code)->delete();
                    # ??????????????????
                    $partModels = [];
                    foreach ($request->get('part_model_unique_code') as $item) {
                        $partModels[] = [
                            'category_unique_code' => $entireModel->category_unique_code,
                            'entire_model_unique_code' => $entireModel->unique_code,
                            'part_model_unique_code' => $item,
                        ];
                    }
                    if (!DB::table('pivot_entire_model_and_part_models')->insert($partModels)) throw new \Exception('????????????????????????');
                }
            });

            return response()->make('????????????');
        } catch (ModelNotFoundException $exception) {
            return response()->make('???????????????', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
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
            if (!$entireModel->trashed()) return Response::make('????????????', 500);

            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return Response::make('????????????', 500);
        }
    }

    /**
     * ??????????????????
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getBatch()
    {
        return view('Entire.Model.batch');
    }

    /**
     * ????????????
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    final public function postBatch(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '??????????????????');
            if (!in_array($request->file('file')->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) return back()->with('????????????excel');
            $currentDate = date('Y-m-d H:i:s');
            $successList = [];
            $nullList = [];
            $excel = DB::transaction(function () use ($request) {
                $excel = ExcelReader::init($request, 'file')->readSheetByName('??????', 2, 0, function ($row) {
                    list($categoryName, $categoryUniqueCode, $entireModelName, $entireModelUniqueCode, $subEntireModelName, $fixCycleValue, $subEntireModelUniqueCode, $factoryName) = $row;

                    # ??????????????????????????????????????????
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
                        # ??????????????????????????????????????????
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
                    # ????????????????????????????????????????????????????????????
                    DB::table('measurements')->where('entire_model_unique_code', $item)->update(['entire_model_unique_code' => $item]);
                }
            });

            return back()->with('success', '????????????');
        } catch (ModelNotFoundException $exception) {
            return $request->ajax() ? response()->make(env('APP_DEBUG') ? '??????????????????' . $exception->getMessage() : '???????????????', 404) : back()->withInput()->with('danger', env('APP_DEBUG') ? '??????????????????' . $exception->getMessage() : '???????????????');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return $request->ajax() ? response()->make(env('APP_DEBUG') ? $eMsg : "????????????", 500) : back()->withInput()->with('danger', env('APP_DEBUG') ? $eMsg : "????????????");
        }
    }

    /**
     * ??????????????????????????????????????????????????????
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
     * ????????????????????????????????????????????????
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    final public function postBatchFactory(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '??????????????????');
            if (!in_array($request->file('file')->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) return back()->with('????????????excel');
            $excel = PivotEntireModelAndFactoryFacade::batch($request, 'file');
            DB::table('pivot_entire_model_and_factories')->insert($excel['success']);

            return $request->ajax() ?
                response()->make('????????????') :
                back()->with('success', '????????????');
        } catch (ModelNotFoundException $exception) {
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    '??????????????????' . $exception->getMessage() :
                    '???????????????', 404) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    '??????????????????' . $exception->getMessage() :
                    '???????????????');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "????????????", 500) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "????????????");
        }
    }

    /**
     * ??????????????????????????????
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
     * ?????????????????????????????????
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
                return "????????????";
                break;
        }
    }

    /**
     * ???????????????????????????
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getNextEntireModelUniqueCode(string $categoryUniqueCode)
    {
        $maxEntireModelUniqueCode = EntireModel::with([])->where('category_unique_code', $categoryUniqueCode)->where('is_sub_model', false)->max('unique_code');
        $uniqueCode = str_replace($categoryUniqueCode, '', $maxEntireModelUniqueCode);
        $nextEntireModelUniqueCode = $categoryUniqueCode . ($uniqueCode ? str_pad(strval(intval($uniqueCode) + 1), 2, "0", STR_PAD_LEFT) : "01");
        return response()->json(['message' => '????????????', 'next' => $nextEntireModelUniqueCode, 'max' => $maxEntireModelUniqueCode]);
    }
}
