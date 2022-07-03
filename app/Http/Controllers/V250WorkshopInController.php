<?php

namespace App\Http\Controllers;

use App\Exceptions\FuncNotFoundException;
use App\Facades\CodeFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\V250WorkshopInEntireInstances;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class V250WorkshopInController extends Controller
{
    /**
     * 现场退回->新建页面
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create(Request $request)
    {
        try {
            $taskOrderSerialNumber = $request->get('sn');

            $v250TaskEntireInstances = V250WorkshopInEntireInstances::with([
                'EntireInstance',
                'EntireInstance.SubModel',
                'EntireInstance.PartModel',
            ])
                ->where('v250_task_orders_serial_number', $taskOrderSerialNumber)
                ->paginate();

            return view('WorkshopIn.create', [
                'taskEntireInstances' => $v250TaskEntireInstances,
                'taskOrderSerialNumber' => $taskOrderSerialNumber
            ]);
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 扫码判断
     * @param Request $request
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function scanCode(Request $request, $sn)
    {
        try {
            $code = $request->get('code');
            $identityCode = DB::table('entire_instances')->where('identity_code', $code)->orWhere('serial_number', $code)->value('identity_code');
            if (!$identityCode) return ['code' => 0, 'msg' =>'设备不存在'];
            if (!DB::table('v250_task_entire_instances')->where('entire_instance_identity_code', $identityCode)->where('v250_task_order_sn', $sn)->exists()) return ['code' => 0, 'msg' =>'该任务中不存在此设备'];
//            if (DB::table('v250_workshop_in_entire_instances')
//                ->where(function ($query) use($sn, $code) {
//                    $query->where('v250_task_orders_serial_number', $sn)
//                        ->where('entire_instance_identity_code', $code);
//                })
//                ->orWhere(function ($query) use($sn, $code) {
//                    $query->where('v250_task_orders_serial_number', $sn)
//                        ->where('entire_instance_serial_number', $code);
//                })
//                ->exists()) return ['code' => 0, 'msg' =>'重复扫码'];

            if (DB::table('v250_workshop_in_entire_instances')
                ->where('v250_task_orders_serial_number', $sn)
                ->where(function ($query) use($sn, $code) {
                    $query->where('entire_instance_identity_code', $code)
                        ->orWhere('entire_instance_serial_number', $code);
                })
                ->exists()) return ['code' => 0, 'msg' =>'重复扫码'];

            $entireInstance = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            DB::table('v250_workshop_in_entire_instances')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'v250_task_orders_serial_number' => $sn,
                'entire_instance_identity_code' => $entireInstance[0]->identity_code,
                'entire_instance_serial_number' => $entireInstance[0]->serial_number,
            ]);
            return ['code' => 1, 'msg' =>'扫码成功'];
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 现场退回->入所
     * @param Request $request
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function workshopIn(Request $request, $sn)
    {
        try {
            $contactName = $request->get('connectionName');
            $contactPhone = $request->get('connectionPhone');
            $date = date("Y-m-d H:i:s");
            $accountNickname =  session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
//            $identityCodes = $request->get('identityCodes');
            $identityCodes = DB::table('v250_workshop_in_entire_instances')->where('v250_task_orders_serial_number', $sn)->get(['entire_instance_identity_code']);
            foreach ($identityCodes as $k=>$identityCode) {
                $identityCodes[$k] = [$identityCode->entire_instance_identity_code];
            }

            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $identityCodes)
                ->get();

            DB::transaction(function () use ($contactName, $contactPhone, $date, $accountNickname, $accountId, $workAreaId, $identityCodes, $entireInstances, $sn) {
                # 生成入所单
                $warehouseReportId = DB::table('warehouse_reports')->insertGetId([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'processor_id' => $accountId,
                    'processed_at' => $date,
                    'connection_name' => $contactName,
                    'connection_phone' => $contactPhone,
                    'type' => 'FIXED',
                    'direction' => 'IN',
                    'serial_number' => CodeFacade::makeSerialNumber('WAREHOUSE_IN'),
                    'work_area_id' => $workAreaId
                ]);
                $warehouseReportSerialNumber = DB::table('warehouse_reports')->where('id', $warehouseReportId)->value('serial_number');


                $entireInstances->each(function ($entireInstance)
                use ($date, $accountNickname, $contactName, $contactPhone, $warehouseReportSerialNumber, $identityCodes, $sn) {

                    # 生成入所单设备表
                    DB::table('warehouse_report_entire_instances')->insert([
                        'created_at' => $date,
                        'updated_at' => $date,
                        'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                    ]);

                    # 生成设备日志
                    EntireInstanceLog::with([])
                        ->create([
                            'name' => '现场退回',
                            'description' => implode('；', [
                                '经办人：' . $accountNickname,
                                '联系人：' . $contactName ?? '' . ' ' . $contactPhone ?? '',
                                '车站：' . @$entireInstance->Station->Parent ? @$entireInstance->Station->Parent->name : '' . ' ' . @$entireInstance->Station->Parent->name ?? '',
                                '安装位置：' . @$entireInstance->maintain_location_code ?? '' . @$entireInstance->crossroad_number ?? '',
                            ]),
                            'entire_instance_identity_code' => $entireInstance->identity_code,
                            'type' => 1,
                            'url' => "/warehouse/report/{$warehouseReportSerialNumber}",
                            'operator_id' => session('account.id'),
                            'station_unique_code' => @$entireInstance->Station->unique_code ?? '',
                        ]);

                    # 修改设备状态
                    $entireInstance->fill([
                        'maintain_workshop_name' => env('JWT_ISS'),
                        'status' => 'FIXED',
                        'maintain_location_code' => null,
                        'crossroad_number' => null,
                        'next_fixing_time' => null,
                        'next_fixing_month' => null,
                        'next_fixing_day' => null,
                    ])
                        ->saveOrFail();

                    DB::table('v250_workshop_in_entire_instances')->where('v250_task_orders_serial_number', $sn)->whereIn('entire_instance_identity_code', $identityCodes)->delete();
                    DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $sn)->whereIn('entire_instance_identity_code', $identityCodes)->update(['is_scene_back' => 1]);
                });
            });
            return JsonResponseFacade::created([],'入所成功');
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 删除
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function destroy($id)
    {
        try {
            DB::table('v250_workshop_in_entire_instances')->where('id', $id)->delete();
            return JsonResponseFacade::deleted();
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 清除
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function destroyAll($sn)
    {
        try {
            DB::table('v250_workshop_in_entire_instances')->where('v250_task_orders_serial_number', $sn)->delete();
            return JsonResponseFacade::deleted();
        } catch (FuncNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }
}
