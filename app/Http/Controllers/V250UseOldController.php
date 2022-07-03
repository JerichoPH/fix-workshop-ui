<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class V250UseOldController extends Controller
{
    /**
     * v250利旧设备列表
     * @param Request $request
     * @return Factory|Application|View
     */
    final public function create(Request $request)
    {
        $categoryUniqueCode = $request->get('categoryUniqueCode');
        $entireModelUniqueCode = $request->get('entireModelUniqueCode');
        $subModelUniqueCode = $request->get('subModelUniqueCode');
        $factoryName = $request->get('factoryName');
        $entireInstanceUniqueCode = $request->get('entireInstanceUniqueCode');
        $sn = $request->get('sn');

        $categories = DB::table('categories')->where('deleted_at', null)->pluck('name', 'unique_code');
        $factories = DB::table('factories')->where('deleted_at', null)->pluck('name');

        $dbQ = DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'position.name as position_name', 'tier.name as tier_name', 'shelf.name as shelf_name', 'platoon.name as platoon_name', 'area.name as area_name', 'storehous.name as storehous_name'])
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
//            ->leftJoin(DB::raw('overhaul_entire_instances oei'), 'oei.entire_instance_identity_code', '=', 'ei.identity_code')
            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
            ->when($categoryUniqueCode, function ($query, $categoryUniqueCode) {
                $query->where('c.unique_code', $categoryUniqueCode);
            })
            ->when($entireModelUniqueCode, function ($query, $entireModelUniqueCode) {
                $query->where('em.unique_code', $entireModelUniqueCode);
            })
            ->when($subModelUniqueCode, function ($query, $subModelUniqueCode) {
                $query->where('sm.unique_code', $subModelUniqueCode);
            })
            ->when($factoryName, function ($query, $factoryName) {
                $query->where('ei.factory_name', $factoryName);
            })
            ->when($entireInstanceUniqueCode, function ($query, $entireInstanceUniqueCode) {
                $query->where('ei.identity_code', $entireInstanceUniqueCode);
            })
            ->whereIn('ei.status', ['FIXING', 'FIXED', 'BUY_IN'])
            ->where('ei.is_overhaul', '0')
            ->where('ei.v250_task_order_sn', '')
            ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
            ->where('ei.deleted_at', null);

        $dbS = DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'position.name as position_name', 'tier.name as tier_name', 'shelf.name as shelf_name', 'platoon.name as platoon_name', 'area.name as area_name', 'storehous.name as storehous_name'])
            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
//            ->leftJoin(DB::raw('overhaul_entire_instances oei'), 'oei.entire_instance_identity_code', '=', 'ei.identity_code')
            ->leftJoin(DB::raw('positions position'), 'ei.location_unique_code', '=', 'position.unique_code')
            ->leftJoin(DB::raw('tiers tier'), 'position.tier_unique_code', '=', 'tier.unique_code')
            ->leftJoin(DB::raw('shelves shelf'), 'tier.shelf_unique_code', '=', 'shelf.unique_code')
            ->leftJoin(DB::raw('platoons platoon'), 'shelf.platoon_unique_code', '=', 'platoon.unique_code')
            ->leftJoin(DB::raw('areas area'), 'platoon.area_unique_code', '=', 'area.unique_code')
            ->leftJoin(DB::raw('storehouses storehous'), 'area.storehouse_unique_code', '=', 'storehous.unique_code')
            ->when($categoryUniqueCode, function ($query, $categoryUniqueCode) {
                $query->where('c.unique_code', $categoryUniqueCode);
            })
            ->when($entireModelUniqueCode, function ($query, $entireModelUniqueCode) {
                $query->where('em.unique_code', $entireModelUniqueCode);
            })
            ->when($subModelUniqueCode, function ($query, $subModelUniqueCode) {
                $query->where('pm.unique_code', $subModelUniqueCode);
            })
            ->when($factoryName, function ($query, $factoryName) {
                $query->where('ei.factory_name', $factoryName);
            })
            ->when($entireInstanceUniqueCode, function ($query, $entireInstanceUniqueCode) {
                $query->where('ei.identity_code', $entireInstanceUniqueCode);
            })
            ->whereIn('ei.status', ['FIXING', 'FIXED', 'BUY_IN'])
            ->where('ei.is_overhaul', '0')
            ->where('ei.v250_task_order_sn', '')
            ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
            ->where('ei.deleted_at', null);

        $db = $dbS->unionAll($dbQ);
        $workshopEntireInstances = DB::table(DB::raw("({$db->toSql()}) as a"))->mergeBindings($db)->paginate(100);
        return view('UseOld.create', [
            'categories' => $categories,
            'factories' => $factories,
            'workshopEntireInstances' => $workshopEntireInstances,
            'sn' => $sn
        ]);
    }

    /**
     * 利旧操作
     * @return mixed
     */
    final public function store(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $sn = $request->get('sn');
                $identityCodes = $request->get('identityCodes');
                foreach ($identityCodes as $identityCode) {
                    DB::table('entire_instances')->where('identity_code', $identityCode)->update(['v250_task_order_sn' => $sn, 'updated_at' => date('Y-m-d H:i:s')]);
                    if (DB::table('overhaul_entire_instances')->where('entire_instance_identity_code', $identityCode)->where('status', '0')->exists()) {
                        $overhaulEntireInstance = DB::table('overhaul_entire_instances')->where('entire_instance_identity_code', $identityCode)->where('status', '0')->get()->toArray();
                    }else {
                        $overhaulEntireInstance = DB::table('overhaul_entire_instances')->where('entire_instance_identity_code', $identityCode)->where('status', '<>', '0')->orderByDesc('created_at')->get()->toArray();
                    }
                    DB::table('v250_task_entire_instances')->insert([
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'v250_task_order_sn' => $sn,
                        'entire_instance_identity_code' => $identityCode,
                        'fixer_id' => @$overhaulEntireInstance[0]->fixer_id ?? 0,
                        'checker_id' => @$overhaulEntireInstance[0]->checker_id ?? 0,
                        'spot_checker_id' => @$overhaulEntireInstance[0]->spot_checker_id ?? 0,
                        'fixed_at' => @$overhaulEntireInstance[0]->fixed_at ?? null,
                        'checked_at' => @$overhaulEntireInstance[0]->checked_at ?? null,
                        'spot_checked_at' => @$overhaulEntireInstance[0]->spot_checked_at ?? null,
                        'is_scene_back' => 0,
                        'is_out' => 0,
                        'out_at' => null,
                        'out_warehouse_sn' => ''
                    ]);
                }
                foreach ($identityCodes as $oldIdentityCode) {
                    $remark[$oldIdentityCode] = '设备器材：' . $oldIdentityCode . '，' . '在新站任务中被使用。详情：工区：' . session('account.work_area');
                }
                # 设备加锁
                EntireInstanceLock::setOnlyLocks($identityCodes, ['NEW_STATION'], $remark);
            });
            return JsonResponseFacade::created([], '操作成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
