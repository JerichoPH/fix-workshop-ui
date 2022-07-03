<?php

namespace App\Http\Controllers\Entire;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BindController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            return view('Entire.Bind.index');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取整件信息
     * @param string $identity_code
     */
    final public function getEntireInstance(string $identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartInstances',
                'PartInstances.Category',
                'PartInstances.EntireModel',
                'PartInstances.SubModel',
                'PartInstances.PartModel',
                'PartInstances.PartCategory',
            ])
                ->where('is_part', false)
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            return JsonResponseFacade::dict([
                'entire_instance' => $entire_instance,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("设备器材没有找到：{$identity_code}");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取部件信息
     * @param string $identity_code
     * @return mixed
     */
    final public function getPartInstance(string $identity_code)
    {
        try {
            $part_instance = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel',
                'PartCategory',
            ])
                ->where('is_part', true)
                ->where('identity_code', $identity_code)
                ->firstOrFail();
            if ($part_instance->entire_instance_identity_code) return JsonResponseFacade::errorValidate('当前部件已经绑定');

            return JsonResponseFacade::dict([
                'part_instance' => $part_instance,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 根据整件编号获取部件列表
     * @param string $entire_instance_identity_code
     */
    final public function getPartInstances(string $entire_instance_identity_code)
    {
        try {
            $part_instances = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel',
                'PartCategory',
            ])
                ->where('is_part', true)
                ->where('entire_instance_identity_code', $entire_instance_identity_code)
                ->get();

            return JsonResponseFacade::dict([
                'part_instances' => $part_instances,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量绑定部件
     * @param Request $request
     * @param string $entire_instance_identity_code
     */
    final public function postBindPartInstances(Request $request, string $entire_instance_identity_code)
    {
        DB::beginTransaction();
        try {
            if (!DB::table('entire_instances as ei')
                ->whereNull('ei.deleted_at')
                ->where('ei.is_part', false)
                ->where('identity_code', $entire_instance_identity_code)
                ->exists()
            ) return JsonResponseFacade::errorValidate("没有找到整件：{$entire_instance_identity_code}");

            $part_instance_identity_codes = $request->get('part_instance_identity_codes');
            if (!$part_instance_identity_codes) return JsonResponseFacade::errorValidate('没有要绑定的部件');
            $diff = [];
            $diff = array_diff($part_instance_identity_codes, DB::table('entire_instances')->select(['identity_code'])->whereNull('deleted_at')->whereIn('identity_code', $part_instance_identity_codes)->where('is_part', true)->get()->pluck('identity_code')->toArray());
            if ($diff) return JsonResponseFacade::errorValidate('以下部件没有找到：' . implode(',', $diff));

            $already_part_instance_identity_codes = DB::table('entire_instances as ei')
                ->select(['identity_code'])
                ->whereNull('deleted_at')
                ->whereIn('identity_code', $part_instance_identity_codes)
                ->where('is_part', true)
                ->where('ei.entire_instance_identity_code', $entire_instance_identity_code)
                ->get()
                ->pluck('identity_code')
                ->toArray();
            $update_data = [];
            $entire_instance_logs = [];
            $part_instance_logs = [];
            foreach ($part_instance_identity_codes as $part_instance_identity_code) {
                if (!in_array($part_instance_identity_code, $already_part_instance_identity_codes)) {
                    $update_data[] = $part_instance_identity_code;

                    // 添加整件日志
                    $entire_instance_logs[] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'name' => '安装部件',
                        'description' => "安装部件：{$entire_instance_identity_code}。操作人：" . session('account.nickname'),
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'type' => 7,
                        'url' => '',
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.nickname'),
                        'station_unique_code' => '',
                    ];

                    $part_instance_logs[] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'name' => '安装部件',
                        'description' => "安装部件：{$entire_instance_identity_code}到整件：{$entire_instance_identity_code}。操作人：" . session('account.nickname'),
                        'entire_instance_identity_code' => $part_instance_identity_code,
                        'type' => 7,
                        'url' => '',
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.nickname'),
                        'station_unique_code' => '',
                    ];
                }
            }

            if ($update_data) {
                EntireInstance::with([])
                    ->where('is_part', true)
                    ->whereIn('identity_code', $update_data)
                    ->update([
                        'updated_at' => now(),
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                    ]);

                // 添加日志
                EntireInstanceLog::with([])->insert($entire_instance_logs);  // 整件日志
                EntireInstanceLog::with([])->insert($part_instance_logs);  // 部件日志
            }
            DB::commit();

            $part_instances = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel',
                'PartCategory',
            ])
                ->where('entire_instance_identity_code', $entire_instance_identity_code)
                ->where('is_part', true)
                ->get();

            return JsonResponseFacade::created(['part_instances' => $part_instances,], "成功安装：" . count($update_data) . '台部件');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            DB::rollBack();
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 拆除部件
     * @param Request $request
     * @param string $identity_code
     * @return mixed
     */
    final public function deleteUnbindPartInstance(Request $request, string $identity_code)
    {
        DB::beginTransaction();
        try {
            $part_instance = EntireInstance::with([])
                ->where('identity_code', $identity_code)
                ->where('is_part', true)
                ->firstOrFail();
            if (!$part_instance->entire_instance_identity_code) return JsonResponseFacade::errorForbidden('当前部件没有绑定整件');
            $entire_instance_identity_code = $part_instance->entire_instance_identity_code;

            $part_instance->fill(['entire_instance_identity_code' => ''])->saveOrFail();

            /**
             * 整件日志
             */
            EntireInstanceLog::with([])->create([
                'name' => '拆除部件',
                'description' => "拆除部件：{$identity_code}。操作人：" . session('account.nickname'),
                'entire_instance_identity_code' => $entire_instance_identity_code,
                'type' => 8,
                'url' => '',
                'material_type' => 'ENTIRE',
                'operator_id' => session('account.nickname'),
                'station_unique_code' => '',
            ]);

            /**
             * 部件日志
             */
            EntireInstanceLog::with([])->create([
                'name' => '拆除部件',
                'description' => "从整件{$entire_instance_identity_code}中拆除部件：{$identity_code}。操作人：" . session('account.nickname'),
                'entire_instance_identity_code' => $identity_code,
                'type' => 8,
                'url' => '',
                'material_type' => 'ENTIRE',
                'operator_id' => session('account.nickname'),
                'station_unique_code' => '',
            ]);

            DB::commit();

            return JsonResponseFacade::deleted([],'拆除成功');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return JsonResponseFacade::errorEmpty('没有找到部件，或部件已经被删除');
        } catch (Throwable $e) {
            DB::rollBack();
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
