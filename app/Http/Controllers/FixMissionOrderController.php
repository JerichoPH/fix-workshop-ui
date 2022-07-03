<?php

namespace App\Http\Controllers;

use App\Exceptions\FixWorkflowException;
use App\Facades\FixWorkflowFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;

class FixMissionOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index(Request $request)
    {
        $workAreaId = $request->get('workAreaId');
        $dates = $request->get('dates');
        if ($workAreaId) {
            if ($dates) {
                $fixMissionOrders = DB::table('fix_mission_orders')->where('serial_number', $dates)->where('work_area_id', $workAreaId)->get()->toArray();
                $fixMissionOrderEntireInstances = DB::table('fix_mission_order_entire_instances')->where('fix_mission_order_serial_number', $dates)->where('work_area_id', $workAreaId)->get()->toArray();
                $datas = DB::table('fix_mission_order_entire_instances')
                    ->join('fix_mission_orders', 'fix_mission_orders.serial_number', 'fix_mission_order_entire_instances.fix_mission_order_serial_number')
                    ->select(['fix_mission_order_entire_instances.model_name', 'fix_mission_orders.complete'])
                    ->where('fix_mission_order_entire_instances.fix_mission_order_serial_number', $dates)
                    ->where('fix_mission_order_entire_instances.work_area_id', $workAreaId)
                    ->groupBy('fix_mission_order_entire_instances.model_name')
                    ->get()
                    ->toArray();
            }else{
                $datas = [];
                $fixMissionOrders = [];
                $fixMissionOrderEntireInstances = [];
            }
        }else {
            $datas = [];
            $fixMissionOrders = [];
            $fixMissionOrderEntireInstances = [];
        }
        return view('Report.FixMissionOrder.fixMissionOrder', [
            'datas' => $datas,
            'fixMissionOrders' => $fixMissionOrders,
            'fixMissionOrderEntireInstances' => $fixMissionOrderEntireInstances]);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        try {
            $selecteds = $request->get('selecteds');
            $dates = $request->get('dates');
            $selWorkArea = $request->get('selWorkArea');
            $selAccount = $request->get('selAccount');
            $deadLine = $request->get('deadLine');
            $created_at = date('Y-m-d H:i:s');
//            $serial_number = DB::table('fix_mission_orders')->orderByDesc('id')->value('serial_number');
//            if ($serial_number) {
//                $serial_number_8 = str_pad(substr($serial_number,-8)+1,8,0,STR_PAD_LEFT);
//                $serial_number = array_keys(config('app.code'))[0].$serial_number_8;
//            }else {
//                $serial_number = array_keys(config('app.code'))[0].'00000001';
//            }
            if (DB::table('fix_mission_orders')->where('serial_number', $dates)->where('work_area_id', $selWorkArea)->exists()) {
                foreach ($selecteds as $k=>$v) {
                    if (DB::table('fix_mission_order_entire_instances')->where('fix_mission_order_serial_number', $dates)->where('entire_instance_identity_code', $v)->exists()) {
                        DB::table('fix_mission_order_entire_instances')->where('fix_mission_order_serial_number', $dates)->where('entire_instance_identity_code', $v)->update([
                            'updated_at' => $created_at,
                            'abort_date' => $deadLine,
                            'account_id' => $selAccount,
                            'work_area_id' => $selWorkArea
                        ]);
                    }else {
                        DB::table('fix_mission_order_entire_instances')->insert([
                            'created_at' => $created_at,
                            'fix_mission_order_serial_number' => $dates,
                            'account_id' => $selAccount,
                            'entire_instance_identity_code' => $v,
                            'abort_date' => $deadLine,
                            'work_area_id' => $selWorkArea,
                            'model_name' => DB::table('entire_instances')->where('identity_code', $v)->value('model_name')
                        ]);
                    }
                }
            }else {
                DB::table('fix_mission_orders')->insert([
                    'created_at' => $created_at,
                    'serial_number' => $dates,
                    'status' => 'PROCESSING',
                    'initiator_id' => session('account.id'),
                    'work_area_id' => $selWorkArea,
                    'type' => 'NONE',
                ]);
                foreach ($selecteds as $k=>$v) {
                    DB::table('fix_mission_order_entire_instances')->insert([
                        'created_at' => $created_at,
                        'fix_mission_order_serial_number' => $dates,
                        'account_id' => $selAccount,
                        'entire_instance_identity_code' => $v,
                        'abort_date' => $deadLine,
                        'work_area_id' => $selWorkArea,
                        'model_name' => DB::table('entire_instances')->where('identity_code', $v)->value('model_name')
                    ]);
                }
            }
            return Response::make('检修任务分配成功');
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        //
    }

    /**
     * 下载Excel
     * @return array|\Illuminate\Http\Response
     */
    final public function DownloadExcel(Request $request){
        try {
            $workAreaId = $request->get('workAreaId');
            $dates = $request->get('dates');
            $fixMissionOrderEntireInstances = DB::table('fix_mission_order_entire_instances')->where('fix_mission_order_serial_number', $dates)->where('work_area_id', $workAreaId)->get()->toArray();
            if (request('download') == 1) {
                # 下载Excel模板
                ExcelWriteHelper::download(function ($excel)use ($fixMissionOrderEntireInstances) {
                    $excel->setActiveSheetIndex(0);
                    $currentSheet = $excel->getActiveSheet();

                    # 字体颜色
                    $red = new \PHPExcel_Style_Color();
                    $red->setRGB('FF0000');
                    $black = new \PHPExcel_Style_Color();
                    $black->setRGB('000000');

                    # 首行
                    $currentSheet->setCellValueExplicit('A1', '时间*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('A1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('B1', '工区*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('B1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('C1', '设备编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('C1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('D1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('D1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('E1', '检修人*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('E1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('F1', '截止日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('F1')->getFont()->setColor($red);
                    $currentSheet->setCellValueExplicit('G1', '验收日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                    $currentSheet->getStyle('G1')->getFont()->setColor($red);
                    $i = 2;
                    foreach ($fixMissionOrderEntireInstances as $v) {
                        $currentSheet->setCellValueExplicit('A' . $i, $v->fix_mission_order_serial_number, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A2')->getFont()->setColor($black);
                        $currentSheet->setCellValueExplicit('B' . $i, Account::$WORK_AREAS[$v->work_area_id], \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B2')->getFont()->setColor($black);
                        $currentSheet->setCellValueExplicit('C' . $i, $v->entire_instance_identity_code, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C2')->getFont()->setColor($black);
                        $currentSheet->setCellValueExplicit('D' . $i, $v->model_name, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D2')->getFont()->setColor($black);
                        $currentSheet->setCellValueExplicit('E' . $i, DB::table('accounts')->where('id', $v->account_id)->value('nickname'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E3')->getFont()->setColor($black);
                        $currentSheet->setCellValueExplicit('F' . $i, substr($v->abort_date,0,10), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F3')->getFont()->setColor($black);
                        $currentSheet->setCellValueExplicit('G' . $i, $v->acceptance_date, \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->getStyle('G3')->getFont()->setColor($black);
                        $i++;
                    }

                    # 定义列宽
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(30);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(4))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(5))->setWidth(30);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(6))->setWidth(30);

                    return $excel;
                }, '批量导入检修任务');
            }
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

    final public function UploadExcel(Request $request)
    {
        try {
            if (!$request->hasFile('upLoadExcel')) return Response::make('上传文件失败', 302);
            if (!in_array($request->file('upLoadExcel')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return Response::make('只能上传excel', 302);

            $created_at = date('Y-m-d H:i:s');
            DB::beginTransaction();
            $originRow = 2;
            $excel = ExcelReadHelper::FROM_REQUEST($request, 'upLoadExcel')->originRow($originRow)->withSheetIndex(0);

            // 线别导入
            foreach ($excel['success'] as $row) {
                $lineName = $row[0];
                if (!DB::table('lines')->where('name', $lineName)->exists()) {
                    $serial_number = DB::table('lines')->orderByDesc('id')->value('unique_code');
                    if ($serial_number) {
                        $serial_number_4 = str_pad(substr($serial_number,-4)+1,4,0,STR_PAD_LEFT);
                        $unique_code = 'E'.$serial_number_4;
                    }else {
                        $unique_code = 'E'.'0001';
                    }
                    DB::table('lines')->insert([
                        'created_at' => $created_at,
                        'name' => $lineName,
                        'unique_code' => $unique_code
                    ]);
                }else {
                    DB::table('lines')->where('name', $lineName)->update([
                        'updated_at' => $created_at
                    ]);
                }
            }
            // 车间导入
            foreach ($excel['success'] as $row) {
                $workShopName = $row[1];
                $lon = $row[7];
                $lat = $row[8];
                $contact = $row[10];
                $contact_phone = $row[9];
                if (!DB::table('maintains')->where('name', $workShopName)->exists()) {
                    $serial_number = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->orderByDesc('id')->value('unique_code');
                    if ($serial_number) {
                        $serial_number_2 = str_pad(substr($serial_number,-2)+1,2,0,STR_PAD_LEFT);
                        $unique_code = 'B049C'.$serial_number_2;
                    }else {
                        $unique_code = 'B049C'.'01';
                    }
                    DB::table('maintains')->insert([
                        'created_at' => $created_at,
                        'unique_code' => $unique_code,
                        'name' => $workShopName,
                        'parent_unique_code' => 'B049',
                        'type' => 'SCENE_WORKSHOP',
                        'lon' => $lon,
                        'lat' => $lat,
                        'contact' => $contact,
                        'contact_phone' => $contact_phone,
                        'is_show' => 1,
                    ]);
                }else {
                    DB::table('maintains')->where('name', $workShopName)->update([
                        'updated_at' => $created_at,
                        'lon' => $lon,
                        'lat' => $lat,
                        'contact' => $contact,
                        'contact_phone' => $contact_phone,
                        'is_show' => 1,
                    ]);
                }
            }
            // 车站导入
            foreach ($excel['success'] as $row) {
                $lineName = $row[0];
                $workShopName = $row[1];
                $stationName = $row[2];
                $lon = $row[3];
                $lat = $row[4];
                $contact = $row[5];
                $contact_phone = $row[6];
                if (!DB::table('maintains')->where('name', $stationName)->exists()) {
                    $serial_number = DB::table('maintains')->where('type', 'STATION')->orderByDesc('id')->value('unique_code');
                    if ($serial_number) {
                        $serial_number_5 = str_pad(substr($serial_number,-5)+1,5,0,STR_PAD_LEFT);
                        $unique_code = 'G'.$serial_number_5;
                    }else {
                        $unique_code = 'G'.'00001';
                    }
                    $parent_unique_code = DB::table('maintains')->where('name', $workShopName)->value('unique_code');
                    $stationId = DB::table('maintains')->insertGetId([
                        'created_at' => $created_at,
                        'unique_code' => $unique_code,
                        'name' => $stationName,
                        'parent_unique_code' => $parent_unique_code,
                        'type' => 'STATION',
                        'lon' => $lon,
                        'lat' => $lat,
                        'contact' => $contact,
                        'contact_phone' => $contact_phone,
                        'contact_address' => null,
                        'is_show' => 1
                    ]);
                    $lineId = DB::table('lines')->where('name', $lineName)->value('id');
                    DB::table('lines_maintains')->insert([
                        'lines_id' => $lineId,
                        'maintains_id' => $stationId
                    ]);
                }else {
                    $parent_unique_code = DB::table('maintains')->where('name', $workShopName)->value('unique_code');
                    $stationId = DB::table('maintains')->where('name', $stationName)->update([
                        'updated_at' => $created_at,
                        'parent_unique_code' => $parent_unique_code,
                        'lon' => $lon,
                        'lat' => $lat,
                        'contact' => $contact,
                        'contact_phone' => $contact_phone,
                        'contact_address' => null,
                        'is_show' => 1
                    ]);
                    DB::table('lines_maintains')->where('maintains_id', $stationId)->delete();
                    $lineId = DB::table('lines')->where('name', $lineName)->value('id');
                    DB::table('lines_maintains')->insert([
                        'lines_id' => $lineId,
                        'maintains_id' => $stationId
                    ]);
                }
            }
            DB::commit();

            return Response::make('导入成功', 200);
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
     * 根据工区获取检修时间
     * @param $workAreaId
     * @return \Illuminate\Http\Response
     */
    final public function fixMissionOrder($workAreaId)
    {
        try {
            $serial_number = DB::table('fix_mission_orders')->where('work_area_id', $workAreaId)->get(['serial_number'])->toArray();
            return $serial_number;
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
}
