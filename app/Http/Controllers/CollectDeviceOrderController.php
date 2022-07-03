<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\CollectDeviceOrder;
use App\Model\CollectDeviceOrderEntireInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class CollectDeviceOrderController extends Controller
{
    /**
     * 下载Excel
     * @param $collectDeviceOrderSN
     */
    final public function getDownload($collectDeviceOrderSN)
    {
        try {
            $collectDeviceOrder = CollectDeviceOrder::with([
                'Processor'
            ])
                ->where('serial_number', $collectDeviceOrderSN)
                ->first();
            if (!$collectDeviceOrder) return JsonResponseFacade::errorEmpty('设备采集单不存在');

            $savePath = 'wechatMiniApp/collectDeviceOrder';
            $filename = $collectDeviceOrderSN;
            if (!is_file(storage_path("{$savePath}/{$filename}.xls"))) $this->_makeExcelForCollectDeviceOrder($collectDeviceOrderSN);

//            return response()->download(storage_path("{$savePath}/{$filename}.xls"), "{$filename}.xls");

            $file = fopen(storage_path("{$savePath}/{$filename}.xls"), "r");
            header("Content-type:text/html;charset=utf-8");
            header("Content-Type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: " . filesize(storage_path("{$savePath}/{$filename}.xls")));
            header("Content-Disposition: attachment; filename=" . $collectDeviceOrder->created_at->format('Y-m-d') . '_' . $collectDeviceOrder->Processor->id . ".xls");
            echo fread($file, filesize(storage_path("{$savePath}/{$filename}.xls")));
            fclose($file);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 生成器件采集单Excel
     * @param string $collectDeviceOrderSN
     * @return string
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function _makeExcelForCollectDeviceOrder(string $collectDeviceOrderSN)
    {
        $collectDeviceOrder = CollectDeviceOrder::with(['CollectDeviceOrderEntireInstances'])->where('serial_number', $collectDeviceOrderSN)->first();
        if ($collectDeviceOrder->CollectDeviceOrderEntireInstances->isEmpty()) return JsonResponseFacade::errorEmpty('没有需要下载的设备');

        $excelData = [];
        $row = 1;
        $collectDeviceOrder->CollectDeviceOrderEntireInstances->each(function ($val) use (&$row, &$excelData) {
            # 计算周期修时间s
            $nextFixingAt = '';
            if ($val->installed_at && $val->cycle_fix_value) {
                $nextFixingAt = Carbon::parse($val->installed_at)->addYears($val->cycle_fix_value)->format('Y-m-d');
            }
            $row++;
            $excelData[$row] = [
                "A" => $val->entire_instance_serial_number,  # 所编号
                "B" => $val->factory_device_code,  # 厂编号
                "C" => $val->factory_name,  # 供应商
                "D" => $val->model_name,  # 型号
                "E" => '',  # 电机编号
                "F" => $val->made_at ? date('Y-m-d', strtotime($val->made_at)) : '',  # 出厂日期
                "G" => $val->last_out_at ? date('Y-m-d',strtotime($val->last_out_at)) : '',  # 上次检修时间/最新出所时间
                "H" => $val->installed_at ? date('Y-m-d', strtotime($val->installed_at)) : '',  # 安装日期
                "I" => $val->cycle_fix_value,  # 周期修
                "J" => $nextFixingAt,  # 下次周期修时间
                "K" => $val->life_year,  # 使用寿命
                "L" => date('Y-m-d', strtotime($val->scarping_at)),  # 报废日期
                "M" => $val->maintain_station_name,  # 站名
                "N" => $val->maintain_location_code,  # 位置
                "O" => '',  # 道岔号
                "P" => '',  # 道岔类型
                "Q" => '',  # 配线制
                "R" => '',  # 开向
                "S" => '',  # 表示杆特征
                "T" => '',  # TID码
                "U" => '',  # 出所日期
            ];
        });

        $savePath = 'wechatMiniApp/collectDeviceOrder';
        if (!is_dir(storage_path($savePath))) FileSystem::init(storage_path($savePath))->makeDir();
        $filename = $collectDeviceOrderSN;

        ExcelWriteHelper::save(function ($excel) use ($excelData) {
            $excel->setActiveSheetIndex(0);
            $currentSheet = $excel->getActiveSheet();

            # 字体颜色
            $red = new \PHPExcel_Style_Color();
            $red->setRGB('FF0000');

            # 表头
            $currentSheet->setCellValueExplicit('A1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('B1', '厂编号', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('C1', '供应商*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('D1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('E1', '电机编号', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('F1', '出厂日期/首次入所日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('G1', '上次检修时间/最新出所时间*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('H1', '安装日期', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('I1', '周期修（年）', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('J1', '下次周期修时间', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('K1', '使用寿命(年)', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('L1', '报废日期', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('M1', '站名*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('N1', '位置*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('O1', '道岔号*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('P1', '道岔类型*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('Q1', '配线制*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('R1', '开向*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('S1', '表示杆特征*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('T1', 'TID码*', \PHPExcel_Cell_DataType::TYPE_STRING);
            $currentSheet->setCellValueExplicit('U1', '出所日期', \PHPExcel_Cell_DataType::TYPE_STRING);

            # 写入数据
            foreach ($excelData as $row => $excelDatum) {
                foreach ($excelDatum as $key => $val) {
                    $currentSheet->setCellValueExplicit("{$key}{$row}", $val, \PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }

            for ($i = 0; $i < 21; $i++) {
                $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($i))->setWidth(25);
            }

            return $excel;
        },
            storage_path("{$savePath}/{$filename}"),
            ExcelWriteHelper::$VERSION_5
        );

        return $filename;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $collectDeviceOrders = CollectDeviceOrder::with(['Processor', 'CollectDeviceOrderEntireInstances'])
                ->when(request('date'), function ($query) {
                    $query->whereBetween('created_at', explode('~', request('date')));
                })
                ->when(request('scene_workshop_unique_code'), function ($query) {
                    $query->whereHas('CollectDeviceOrderEntireInstances', function ($CollectDeviceOrderEntireInstances) {
                        $CollectDeviceOrderEntireInstances->where('maintain_workshop_unique_code', request('scene_workshop_unique_code'));
                    });
                })
                ->when(request('station_unique_code'), function ($query) {
                    $query->whereHas('CollectDeviceOrderEntireInstances', function ($CollectDeviceOrderEntireInstances) {
                        $CollectDeviceOrderEntireInstances->where('maintain_station_unique_code', request('scene_workshop_unique_code'));
                    });
                })
                ->orderByDesc('id')
                ->paginate();

            return view('CollectDeviceOrder.index', [
                'collectDeviceOrders' => $collectDeviceOrders,
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect('/')->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/')->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param $serialNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($serialNumber)
    {
        try {
            $collectDeviceOrderEntireInstances = CollectDeviceOrderEntireInstance::with([])->where('collect_device_order_sn', $serialNumber)->paginate();

            return view('CollectDeviceOrder.show', [
                'collectDeviceOrderEntireInstances' => $collectDeviceOrderEntireInstances,
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect('/collectDeviceOrder')->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('/collectDeviceOrder')->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        //
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
        //
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
}
