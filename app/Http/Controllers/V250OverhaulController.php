<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\V250TaskOrder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class V250OverhaulController extends Controller
{
    /**
     * v250检修分配首页
     * @param Request $request
     * @return Factory|Application|View
     */
    final public function index(Request $request)
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
            ->whereIn('ei.status', ['FIXING', 'BUY_IN'])
            ->where('ei.is_overhaul', '0')
            ->where('ei.v250_task_order_sn', '')
//            ->whereIn('oei.status', ['1', '2'])
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
            ->whereIn('ei.status', ['FIXING', 'BUY_IN'])
            ->where('ei.is_overhaul', '0')
            ->where('ei.v250_task_order_sn', '')
//            ->whereIn('oei.status', ['1', '2'])
            ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
            ->where('ei.deleted_at', null);

        $db = $dbS->unionAll($dbQ);
        $workshopEntireInstances = DB::table(DB::raw("({$db->toSql()}) as a"))->mergeBindings($db)->paginate(100);

        $dbQ1 = DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'position.name as position_name', 'tier.name as tier_name', 'shelf.name as shelf_name', 'platoon.name as platoon_name', 'area.name as area_name', 'storehous.name as storehous_name'])
            ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.entire_model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('v250_task_entire_instances vte'), 'vte.entire_instance_identity_code', '=', 'ei.identity_code')
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
            ->where('ei.v250_task_order_sn', $sn)
            ->where('ei.is_overhaul', '0')
            ->where('vte.fixer_id', 0)
//            ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
            ->where('ei.deleted_at', null);

        $dbS1 = DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.serial_number', 'ei.model_name', 'ei.factory_name', 'ei.factory_device_code', 'ei.made_at', 'ei.status', 'ei.location_unique_code', 'position.name as position_name', 'tier.name as tier_name', 'shelf.name as shelf_name', 'platoon.name as platoon_name', 'area.name as area_name', 'storehous.name as storehous_name'])
            ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->join(DB::raw('v250_task_entire_instances vte'), 'vte.entire_instance_identity_code', '=', 'ei.identity_code')
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
            ->where('ei.v250_task_order_sn', $sn)
            ->where('ei.is_overhaul', '0')
            ->where('vte.fixer_id', 0)
//            ->where('ei.work_area_unique_code', session('account.work_area_unique_code'))
            ->where('ei.deleted_at', null);

        $db1 = $dbS1->unionAll($dbQ1);
        $taskEntireInstances = DB::table(DB::raw("({$db1->toSql()}) as a"))->mergeBindings($db1)->get();

        $taskOrder = V250TaskOrder::with([
            'WorkAreaByUniqueCode'
        ])
            ->where('serial_number', $sn)
            ->firstOrFail();

        // 获取未来一年的年月
        for ($i = 0; $i <= 12; $i++) {
            $dates[] = date('Y-m', strtotime("+$i months"));
        }
        // 获取本年所有月
        for ($i=1; $i <=12 ; $i++) {
            $yearMonths[$i] = date('Y').'-'.str_pad(substr($i,-2),2,0,STR_PAD_LEFT);;
        }

        // 检修统计
        $overhaulStatistics = DB::table('overhaul_entire_instances as oei')
            ->join(DB::raw('accounts a'), 'a.id', '=', 'oei.fixer_id')
            ->select(['a.id as accountId', 'a.nickname as accountNickname', 'a.work_area_unique_code', 'oei.*'])
            ->where('a.work_area_unique_code', session('account.work_area_unique_code'))
            ->groupBy('fixer_id')
            ->get();

        return view('Overhaul.index', [
            'categories' => $categories,
            'factories' => $factories,
            'workshopEntireInstances' => $workshopEntireInstances,
            'taskEntireInstances' => $taskEntireInstances,
            'sn' => $sn,
            'dates' => $dates,
            'yearMonths' => $yearMonths,
            'overhaulStatistics' => $overhaulStatistics,
            'taskOrder' => $taskOrder
        ]);
    }

    /**
     * 任务内检修分配->检修分配
     * @param Request $request
     * @param $sn
     * @return mixed
     */
    final public function storeOverhaul(Request $request, $sn)
    {
        try {
            DB::transaction(function () use ($request, $sn) {
                $selected_for_fix_misson = $request->get('selected_for_fix_misson');
                $accountId = $request->get('selAccountId');
                DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $sn)->whereIn('entire_instance_identity_code', $selected_for_fix_misson)->update(['fixer_id' => $accountId]);

                DB::table('entire_instances')->whereIn('identity_code', $selected_for_fix_misson)->update([
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 'FIXING',
                    'is_overhaul' => '1'
                ]);

                foreach ($selected_for_fix_misson as $identityCode) {
                    DB::table('overhaul_entire_instances')->insert([
                        'created_at' => date('Y-m-d H:i:s'),
                        'v250_task_order_sn' => $sn,
                        'entire_instance_identity_code' => $identityCode,
                        'fixer_id' => $accountId,
                        'allocate_at' => date('Y-m-d'),
                        'deadline' => DB::table('v250_task_orders')->where('serial_number', $sn)->value('expiring_at')
                    ]);
                }
            });
            return JsonResponseFacade::created([], '分配成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 所内检修分配->检修分配
     * @param Request $request
     * @param $sn
     * @return mixed
     */
    final public function storeOverhaul1(Request $request, $sn)
    {
        try {
            DB::transaction(function () use ($request, $sn) {
                $selected_for_fix_misson = $request->get('selected_for_fix_misson');
                $dates = $request->get('dates');
                $accountId = $request->get('selAccountId');
                $deadLine = $request->get('deadLine');

                DB::table('entire_instances')->whereIn('identity_code', $selected_for_fix_misson)->update([
                    'updated_at' => date('Y-m-d H:i:s'),
                    'status' => 'FIXING',
                    'is_overhaul' => '1'
                ]);

                foreach ($selected_for_fix_misson as $identityCode) {
                    DB::table('overhaul_entire_instances')->insert([
                        'created_at' => date('Y-m-d H:i:s'),
                        'entire_instance_identity_code' => $identityCode,
                        'fixer_id' => $accountId,
                        'allocate_at' => $dates. '-01',
                        'deadline' => $deadLine
                    ]);
                }
            });
            return JsonResponseFacade::created([], '分配成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
