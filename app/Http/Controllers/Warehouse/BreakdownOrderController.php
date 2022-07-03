<?php

namespace App\Http\Controllers\Warehouse;

use App\Facades\EntireInstanceFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstanceLock;
use App\Model\OutEntireInstanceCorrespondence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;

/**
 * 状态修
 * Class BreakdownOrderController
 * @package App\Http\Controllers\Warehouse
 */
class BreakdownOrderController extends Controller
{
    private $_current_time = null;
    private $_lock_name = '';

    public function __construct()
    {
        $this->_current_time = Carbon::now()->format('Y-m-d H:i:s');
        $this->_lock_name = 'BREAKDOWN';
    }

    /**
     * 出所页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function indexForOut()
    {
        try {
            $out_entire_instance_correspondences = OutEntireInstanceCorrespondence::with(['WithEntireInstanceNew'])->where('account_id', session('account.id', 0))->where('out_warehouse_sn', '')->where('new', '<>', '')->get();

            return view('Warehouse.Report.BreakDownOrder.indexOut', [
                'out_entire_instance_correspondences' => $out_entire_instance_correspondences
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 出所 扫码
     * @param Request $request
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function outWithScan(Request $request, string $code)
    {
        try {
            $db = DB::table('entire_instances as ei')
                ->select(['ei.identity_code'])
                ->where('ei.deleted_at', null)
                ->where('ei.status', '<>', 'SCRAP');

            switch ($request->get('searchType', '唯一编号')) {
                case '唯一编号':
                    $entireInstance = $db->where('ei.identity_code', $code)->first();
                    break;
                case '所编号':
                    $entireInstance = $db->where('ei.serial_number', $code)->first();
                    break;
                case '厂编号':
                    $entireInstance = $db->where('status', 'BUY_IN')->where('ei.factory_device_code', $code)->first();
                    break;
                default:
                    $entireInstance = null;
                    break;
            }
            if (empty($entireInstance)) return HttpResponseHelper::errorEmpty('设备不存在');

            $out_entire_instance_correspondence = OutEntireInstanceCorrespondence::with([])->where('new', $entireInstance->identity_code)->where('account_id', session('account.id', 0))->where('out_warehouse_sn', '')->firstOrFail();
            if ($out_entire_instance_correspondence->is_scan == 1) return HttpResponseHelper::errorValidate('重复扫码');
            $out_entire_instance_correspondence->fill(['is_scan' => 1]);
            $out_entire_instance_correspondence->saveOrFail();

            return HttpResponseHelper::created('扫码成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 出所
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function outStore(Request $request)
    {
        try {
            $work_area = session('account.work_area');
            if (array_flip(Account::$WORK_AREAS)[$work_area] == 0) return HttpResponseHelper::errorValidate('该用户没有所属工区');
            $is_check = DB::table('out_entire_instance_correspondences')->where('out_warehouse_sn', '')->where('account_id', session('account.id', 0))->where('is_scan', 0)->where('new', '<>', '')->get();
            if (!$is_check->isEmpty()) return HttpResponseHelper::errorValidate('有设备未扫码');
            $out_entire_instance_correspondences = DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->where('is_scan', 1)->where('out_warehouse_sn', '')->where('new', '<>', '')->pluck('new', 'old')->toArray();
            if (empty($out_entire_instance_correspondences)) return HttpResponseHelper::errorEmpty('数据不存在');
            $oldCodes = array_keys($out_entire_instance_correspondences);
            $newCodes = array_values($out_entire_instance_correspondences);
            EntireInstanceLock::freeLocks(
                array_merge($oldCodes, $newCodes),
                [$this->_lock_name],
                function () use ($newCodes, $oldCodes, $request) {
                    $out_warehouse_sn = WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                        $newCodes,
                        $request->get('processor_id'),
                        $request->get('processed_at'),
                        'NORMAL',
                        $request->get('connection_name', ''),
                        $request->get('connection_phone', '')
                    );
                    DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $newCodes)->update(['status' => 'TRANSFER_OUT']);
                    DB::table('part_instances')->where('deleted_at', null)->whereIn('entire_instance_identity_code', $newCodes)->update(['status' => 'TRANSFER_OUT']);
                    DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->whereIn('old', $oldCodes)->where('out_warehouse_sn', '')->update(['out_warehouse_sn' => $out_warehouse_sn]);
                }
            );

            return HttpResponseHelper::created('出所成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error('line=====' . $exception->getLine() . '====message=====' . $exception->getMessage() . '=====file======' . $exception->getFile());
        }
    }

    /**
     * 出所模态框
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function outWithModal()
    {
        try {
            return view('Warehouse.Report.BreakDownOrder.outWithModal');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 状态修设备页面
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function outWithEntireInstanceIndex(Request $request)
    {
        try {
            $out_entire_instance_correspondences = OutEntireInstanceCorrespondence::with(['WithEntireInstanceOld'])->where('account_id', session('account.id', 0))->where('out_warehouse_sn', '')->paginate();
            $entire_instances = DB::table('entire_instances as ei')
                ->select(['ei.model_name', 'ei.identity_code'])
                ->leftJoin(DB::raw('entire_instance_locks eil'), 'ei.identity_code', '=', 'eil.entire_instance_identity_code')
                ->where('status', 'FIXED')
                ->where('eil.entire_instance_identity_code', null)
                ->get();
            $new_entire_instances = [];
            foreach ($entire_instances as $entire_instance) $new_entire_instances[$entire_instance->model_name][] = $entire_instance->identity_code;

            return view('Warehouse.Report.BreakDownOrder.outWithEntireInstance', [
                'out_entire_instance_correspondences' => $out_entire_instance_correspondences,
                'new_entire_instances' => $new_entire_instances,
            ]);

        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 状态修设备 添加
     * @param Request $request
     * @param $identityCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function outWithEntireInstanceStore(Request $request, $identityCode)
    {
        try {
            $old_entire_unique_code = EntireInstanceFacade::toDecode($identityCode);
            if (empty($old_entire_unique_code)) return HttpResponseHelper::errorEmpty('设备编号不存在');
            $out_entire_instance_correspondence = DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->where('old', $old_entire_unique_code)->where('out_warehouse_sn', '')->first();
            if (!empty($out_entire_instance_correspondence)) return HttpResponseHelper::errorValidate('设备已经添加');
            $old = DB::table('entire_instances as ei')->where('ei.identity_code', $old_entire_unique_code)->first(['maintain_station_name', 'maintain_location_code', 'rfid_code']);
            if (empty($old)) return HttpResponseHelper::errorEmpty('设备不存在');
            EntireInstanceLock::setOnlyLock(
                $old_entire_unique_code,
                [$this->_lock_name],
                self::makeLockRemark($old_entire_unique_code),
                function () use ($old_entire_unique_code, $old) {
                    DB::table('out_entire_instance_correspondences')->insert([
                        'old' => $old_entire_unique_code,
                        'station' => empty($old->maintain_station_name) ? '' : $old->maintain_station_name,
                        'location' => empty($old->maintain_location_code) ? '' : $old->maintain_location_code,
                        'old_tid' => empty($old->rfid_code) ? '' : strval($old->rfid_code),
                        'new_tid' => empty($old->rfid_code) ? '' : strval($old->rfid_code),
                        'account_id' => session('account.id', 0),
                    ]);
                }
            );

            return HttpResponseHelper::created('成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 生成状态修锁备注
     * @param string $code
     * @return string
     */
    final public static function makeLockRemark(string $code)
    {
        return "设备器材：{$code}，在状态修中使用。";
    }

    /**
     * 状态修设备 编辑 替换
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function outWithEntireInstanceUpdate(Request $request)
    {
        try {
            $oldCode = $request->get('oldIdentityCode', '');
            $newCode = $request->get('newIdentityCode', '');
            $old = DB::table('entire_instances as ei')->where('ei.identity_code', $oldCode)->first(['maintain_station_name', 'maintain_location_code', 'rfid_code']);
            if (empty($old)) return HttpResponseHelper::errorEmpty('老设备找不到');
            $oe = DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->where('old', $oldCode)->where('out_warehouse_sn', '')->first();
            if (empty($newCode)) {
                # 取消替换
                EntireInstanceLock::freeLock(
                    $oe->new,
                    [$this->_lock_name],
                    function () use ($oe) {
                        DB::table('entire_instances as ei')->where('ei.identity_code', $oe->new)->update(['updated_at' => $this->_current_time, 'maintain_station_name' => '', 'maintain_location_code' => '']);
                        DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->where('id', $oe->id)->update(['new' => '',]);
                    }
                );

            } else {
                # 替换
                $new = DB::table('entire_instances as ei')->where('ei.identity_code', $newCode)->first(['rfid_code']);
                if (empty($new)) return HttpResponseHelper::errorEmpty('新设备找不到');
                $entireInstanceLock = function () use ($newCode, $oldCode, $oe) {
                    # 新设备加锁
                    EntireInstanceLock::setOnlyLock(
                        $newCode,
                        [$this->_lock_name],
                        self::makeLockRemark($newCode),
                        function () use ($newCode, $oldCode, $oe) {
                            # 给新设备赋位置
                            DB::table('entire_instances as ei')->where('ei.identity_code', $newCode)->update(['updated_at' => $this->_current_time, 'maintain_station_name' => $oe->station, 'maintain_location_code' => $oe->location]);
                            # 旧设备清楚位置
                            DB::table('entire_instances as ei')->where('ei.identity_code', $oldCode)->update(['updated_at' => $this->_current_time, 'maintain_station_name' => '', 'maintain_location_code' => '']);
                            # 修改替换记录
                            DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->where('id', $oe->id)->update([
                                'new' => $newCode,
                            ]);
                        }
                    );
                    return true;
                };

                if (empty($oe->new)) {
                    # 新设备加锁
                    $entireInstanceLock();
                } else {
                    # 原来绑定的解锁，并且清除位置
                    EntireInstanceLock::freeLock(
                        $oe->new,
                        [$this->_lock_name],
                        function () use ($entireInstanceLock, $oe, $newCode, $oldCode) {
                            DB::table('entire_instances as ei')->where('ei.identity_code', $oe->new)->update(['updated_at' => $this->_current_time, 'maintain_station_name' => '', 'maintain_location_code' => '']);
                            $entireInstanceLock();
                        }
                    );
                }


            }
            return HttpResponseHelper::created('成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 状态修设备 删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function outWithEntireInstanceDestories(Request $request)
    {
        try {
            $ids = $request->get('ids', []);
            $out_entire_instance_correspondences = DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->whereIn('id', $ids)->pluck('new', 'old')->toArray();
            if (empty($out_entire_instance_correspondences)) return HttpResponseHelper::errorEmpty('数据不存在');
            $oldCodes = array_keys($out_entire_instance_correspondences);
            $newCodes = array_values($out_entire_instance_correspondences);
            EntireInstanceLock::freeLocks(
                array_merge($newCodes, $oldCodes),
                [$this->_lock_name],
                function () use ($oldCodes, $newCodes) {
                    DB::table('out_entire_instance_correspondences')->where('account_id', session('account.id', 0))->whereIn('old', $oldCodes)->delete();
                    DB::table('entire_instances')->whereIn('identity_code', $newCodes)->update(['updated_at' => $this->_current_time, 'maintain_station_name' => '', 'maintain_location_code' => '']);
                }
            );

            return HttpResponseHelper::created('删除成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

}
