<?php

namespace App\Http\Controllers;

use App\Exceptions\FuncNotFoundException;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\V250WorkshopOutEntireInstances;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class V250WorkshopOutController extends Controller
{
    /**
     * 出所->新建页面
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create(Request $request)
    {
        try {
            $taskOrderSerialNumber = $request->get('sn');

            $v250TaskEntireInstances = V250WorkshopOutEntireInstances::with([
                'EntireInstance',
                'EntireInstance.SubModel',
                'EntireInstance.PartModel',
                'EntireInstance.WithPosition',
                'WithPosition',
                'WithPosition.WithTier',
                'WithPosition.WithTier.WithShelf',
                'WithPosition.WithTier.WithShelf.WithPlatoon',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
            ])
                ->where('v250_workshop_stay_out_serial_number', $taskOrderSerialNumber)
                ->paginate();

            return view('WorkshopOut.create', [
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
     * 2.5.0新站任务->出所 打开出所模态框判断
     * @param Request $request
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function judge(Request $request, $sn)
    {
        try {
            if (DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->where('is_scan_code', '1')->exists())
                return JsonResponseFacade::ok('扫码成功');
            return JsonResponseFacade::errorEmpty('请先扫码');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
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
            if (!$identityCode) return JsonResponseFacade::errorEmpty('设备不存在');
            if (!DB::table('v250_workshop_out_entire_instances')->where('entire_instance_identity_code', $identityCode)->where('v250_workshop_stay_out_serial_number', $sn)->exists()) return ['code' => 0, 'msg' => '待出所单不存在此设备'];

            DB::table('v250_workshop_out_entire_instances')
                ->where('entire_instance_identity_code', $identityCode)
                ->where('v250_workshop_stay_out_serial_number', $sn)
                ->update([
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_scan_code' => '1'
                ]);

            return JsonResponseFacade::created([], '扫码成功');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 出所
     * @param Request $request
     * @param $sn
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function workshopOut(Request $request, $sn)
    {
        try {
            $contactName = $request->get('connectionName');
            $contactPhone = $request->get('connectionPhone');
            $date = date("Y-m-d H:i:s");
            $accountNickname = session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
            $identityCodes = DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->where('is_scan_code', '1')->get(['v250_task_orders_serial_number', 'entire_instance_identity_code']);
            foreach ($identityCodes as $k => $identityCode) {
                $serialNumber = $identityCode->v250_task_orders_serial_number;
                $identityCodes[$k] = [$identityCode->entire_instance_identity_code];
            }
            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $identityCodes)
                ->get();

            DB::beginTransaction();
            # 生成出所单
            $warehouseReportId = DB::table('warehouse_reports')
                ->insertGetId([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'processor_id' => $accountId,
                    'processed_at' => $date,
                    'connection_name' => $contactName,
                    'connection_phone' => $contactPhone,
                    'type' => 'INSTALL',
                    'direction' => 'OUT',
                    'serial_number' => CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
                    'work_area_id' => $workAreaId
                ]);
            $warehouseReportSerialNumber = DB::table('warehouse_reports')->where('id', $warehouseReportId)->value('serial_number');

            $entireInstances
                ->each(
                    function ($entireInstance) use (
                        $request,
                        $date,
                        $accountNickname,
                        $contactName,
                        $contactPhone,
                        $warehouseReportSerialNumber
                    ) {
                        # 修改设备状态
                        $entireInstance->fill([
                            'updated_at' => $date,
                            'last_out_at' => $date,
                            'status' => 'TRANSFER_OUT',
                            'next_fixing_time' => null,
                            'next_fixing_month' => null,
                            'next_fixing_day' => null,
                            'in_warehouse_breakdown_explain' => '',
                            'last_warehouse_report_serial_number_by_out' => $warehouseReportSerialNumber,
                            'location_unique_code' => '',
                            'is_bind_location' => 0,
                            'is_overhaul' => '0'
                        ])
                            ->saveOrFail();

                        # 重新计算周期修
                        EntireInstanceFacade::nextFixingTimeWithIdentityCode($entireInstance->identity_code, $date);

                        # 生成出所单设备表
                        DB::table('warehouse_report_entire_instances')->insert([
                            'created_at' => $date,
                            'updated_at' => $date,
                            'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                            'entire_instance_identity_code' => $entireInstance->identity_code,
                        ]);

                        # 生成设备日志
                        EntireInstanceLog::with([])
                            ->create([
                                'name' => '出所',
                                'description' => implode('；', [
                                    '经办人：' . $accountNickname,
                                    '联系人：' . $contactName ?? '无',
                                    '联系电话：' . $contactPhone ?? '无',
                                    '现场车间：' . @$entireInstance->Station->Parent->name ?: '无',
                                    '车站：' . @$entireInstance->Station->name ?: '',
                                ]),
                                'entire_instance_identity_code' => $entireInstance->identity_code,
                                'type' => 1,
                                'url' => "/warehouse/report/{$warehouseReportSerialNumber}",
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$entireInstance->Station->unique_code ?? '',
                            ]);
                    }
                );
            if (!DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->where('is_scan_code', '0')->exists()) {
                DB::table('v250_workshop_stay_out')->where('serial_number', $sn)->update([
                    'updated_at' => $date,
                    'finished_at' => $date,
                    'status' => 'DONE',
                ]);
            }

            DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->where('is_scan_code', '1')->delete();
            DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $serialNumber)->whereIn('entire_instance_identity_code', $identityCodes)->update(['is_out' => 1]);
            DB::commit();
            return JsonResponseFacade::ok('出所成功');
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
            DB::table('v250_workshop_out_entire_instances')->where('id', $id)->delete();
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
            DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->delete();
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
