<?php

namespace App\Http\Controllers\Measurement;

use App\Facades\CodeFacade;
use App\Facades\ExcelReader;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MeasurementRequest;
use App\Model\EntireModel;
use App\Model\Measurement;
use App\Model\PivotFromWarehouseProductToWarehouseProductPart;
use App\Model\WarehouseProduct;
use App\Model\WarehouseProductPart;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->has('entireModelUniqueCode')) {
            $measurements = Measurement::with(['EntireModel', 'EntireModel.Category', 'PartModel'])->
            orderByDesc('id')
                ->where('entire_model_unique_code', request('entireModelUniqueCode'))
                ->paginate();
            $type = 'product';
        } else {
            $measurements = Measurement::with(['EntireModel', 'EntireModel.Category', 'PartModel'])
                ->orderByDesc('id')
                ->paginate();
            $type = 'self';
        }
//        dd($measurements);
        return view('Measurement.Post.index')
            ->with('entireModelUniqueCode', request('entireModelUniqueCode'))
            ->with('measurements', $measurements)
            ->with('type', $type);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        try {
            if (request()->ajax()) {
                // 读取整件和零件数据
                switch (request('type')) {
                    case 'product':
                        $warehouseProduct = WarehouseProduct::where('id', request('warehouseProductId'))->firstOrFail();
                        $warehouseProductParts = WarehouseProductPart::whereIn('id', PivotFromWarehouseProductToWarehouseProductPart::where('warehouse_product_id', $warehouseProduct->id)->pluck('warehouse_product_part_id')->toArray())->get();
                        return view('Measurement.Post.create_ajax_product', ['warehouseProduct' => $warehouseProduct, 'warehouseProductParts' => $warehouseProductParts]);
                        break;
                    case 'part':
                        return Response::make('类型错误', 500);
                        break;
                    case 'self':
                        break;
                    default:
                        return Response::make('类型错误', 500);
                        break;
                }
            } else {
                switch (request('type')) {
                    case 'product':
                        $warehouseProduct = WarehouseProduct::where('id', request('warehouseProductId'))->firstOrFail();
                        $warehouseProductParts = WarehouseProductPart::whereIn('id', PivotFromWarehouseProductToWarehouseProductPart::where('warehouse_product_id', $warehouseProduct->id)->pluck('warehouse_product_part_id')->toArray())->get();
                        return view('Measurement.Post.create_product', ['warehouseProduct' => $warehouseProduct, 'warehouseProductParts' => $warehouseProductParts]);
                        break;
                    case 'part':
                        break;
                    case 'self':
                    default:
                        $entireModels = EntireModel::orderByDesc('id')->pluck('name', 'unique_code');
                        return view('Measurement.Post.create_self')
                            ->with('entireModels', $entireModels);
                        break;
                        break;
                }
            }
            return Response::make();
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误' . $exception->getMessage(), 500);
        }
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
            $v = Validator::make($request->all(), MeasurementRequest::$RULES, MeasurementRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            # 生成身份码
            $identityCode = CodeFacade::makeMeasurementIdentityCode($request->get('entire_model_unique_code'), $request->get('part_model_unique_code'));
            $measurement = new Measurement;
            $measurement->fill(array_merge($request->all(), ['identity_code' => $identityCode]))->saveOrFail();

            return Response::make('新建成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误' . $exceptionMessage, 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
            $measurement = Measurement::findOrFail($id);
            $entireModels = EntireModel::orderByDesc('id')->pluck('name', 'unique_code');
            return view('Measurement.Post.edit')
                ->with('measurement', $measurement)
                ->with('entireModels', $entireModels);
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
            $v = Validator::make($request->all(), MeasurementRequest::$RULES, MeasurementRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            $measurement = Measurement::findOrFail($id);
            $measurement->fill($request->all())->saveOrFail();

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
            $measurement = Measurement::findOrFail($id);
            $measurement->delete();
            if (!$measurement->trashed()) return Response::make('删除失败', 500);

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
    public final function getBatch()
    {
        return view('Measurement.Post.batch');
    }

    /**
     * 批量导入
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public final function postBatch(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '文件上传失败');
            if (!in_array($request->file('file')->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) return back()->with('只能上传excel');
            $excel = ExcelReader::init($request, 'file')->readSheetByName('Sheet1', 2, 0, function ($row) {
//                list($entireModelName, $partModelName, $key, $allow_min, $allow_max, $unit, $character) = $row;
                list($entireModelName, $partModelName, $key, $allow_min, $allow_max, $unit, $character, $operation) = $row;
                if ($entireModelName == '----') return null;
                $entireModelUniqueCode = collect(DB::table('entire_models')->where('name', $entireModelName)->first(['unique_code']))->toArray();
                $partModelUniqueCode = null;
                if ($partModelName) $partModelUniqueCode = collect(DB::table('part_models')->where('name', $partModelName)->first(['unique_code']))->toArray();
                if ($entireModelUniqueCode) {
                    return [
                        'entire_model_unique_code' => $entireModelUniqueCode['unique_code'],
                        'part_model_unique_code' => $partModelUniqueCode ? $partModelUniqueCode['unique_code'] : null,
                        'key' => $key,
                        'allow_min' => $allow_min,
                        'allow_max' => $allow_max,
                        'unit' => $unit,
                        'character' => $character,
                        'operation' => $operation
                    ];
//                return "型号名称：{$entireModelName}，型号代码：{$entireModelUniqueCode->unique_code}，测试项：{$key}，最小值：{$allow_min}，最大值：{$allow_max}，单位：{$unit}，特性：{$character}";
                } else {
                    null;
                }
            });
            \App\Facades\MeasurementFacade::batch($excel['success']);

            $successCount = count($excel['success']);
            $failCount = count($excel['fail']);
            session()->put('measurementReport', ['success' => $excel['success'], 'fail' => $excel['fail']]);

            return back()->with('success', "成功导入：{$successCount}条，失败：{$failCount}条。&nbsp;&nbsp;<a href='" . url('measurements/batch/report') . "'>查看详情</a>");
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return back()->with('danger', env('APP_DEBUG') ? "{$eMsg}<br>{$eFile}<br>{$eLine}" : "意外错误");
        }
    }

    /**
     * 上传详细报告
     */
    public function getBatchReport()
    {
//        Storage::disk('local')->put('measurement.fail.' . date('Y-m-d H:i:s') . '.json', Text::toJson(session('measurementReport.fail')));
        return view('Measurement.Post.batchReport')
            ->with('success', session('measurementReport.success'))
            ->with('fail', session('measurementReport.fail'));
    }
}
