<?php

namespace App\Http\Controllers;

use App\Facades\BreakdownLogFacade;
use App\Facades\JsonResponseFacade;
use App\Model\BreakdownType;
use App\Model\EntireInstance;
use App\Model\PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes;
use App\Model\RepairBaseBreakdownOrderTempEntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BreakdownLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $breakdown_types = BreakdownType::with([])->pluck('name', 'id')->chunk(3)->toArray();

            return view('BreakdownLog.create_ajax', [
                'breakdown_types' => $breakdown_types,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $entire_instance = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->where('identity_code', $request->get('entire_instance_identity_code'))
                ->firstOrFail();

            return DB::transaction(function () use ($request, $entire_instance) {
                $warehouse_in = function () use ($entire_instance, $request) {
                    # 入所故障描述
                    BreakdownLogFacade::createWarehouseIn(
                        $entire_instance,
                        $request->get('explain') ?? '',
                        $request->get('submitted_at'),
                        $request->get('breakdown_type_ids', []) ?? [],
                        $request->get('submitter_name')
                    );

                    return response()->json(['message' => '添加成功']);
                };

                $station = function () use ($entire_instance, $request) {
                    if (!$request->get('submitted_at')) return response()->json(['message' => '故障时间不能为空'], 403);
                    // if (!$request->get('submitter_name')) return response()->json(['message' => '上报人不能为空'], 403);
                    // if (!$request->get('crossroad_number')) return response()->json(['message' => '道岔号不能为空'], 403);
                    if (!$request->get('explain')) return response()->json(['message' => '故障描述不能为空'], 403);

                    # 现场故障描述
                    BreakdownLogFacade::createStation(
                        $entire_instance,
                        $request->get('explain') ?? '',
                        $request->get('submitted_at') ?? '',
                        $request->get('crossroad_number') ?? '',
                        $request->get('submitter_name') ?? ''
                    );

                    return response()->json(['message' => '添加成功']);
                };

                $func = strtolower(request('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
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
     * @param $id
     * @return RepairBaseBreakdownOrderTempEntireInstance|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Http\JsonResponse
     */
    final public function edit($id)
    {
        try {
            $breakdown_order_temp_entire_instance = RepairBaseBreakdownOrderTempEntireInstance::with(['BreakdownTypes'])->where('id', $id)->firstOrFail();

            return $breakdown_order_temp_entire_instance;
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function update(Request $request, $id)
    {
        try {
            # 获取是临时表对应数据
            RepairBaseBreakdownOrderTempEntireInstance::with([])->where('id', $id)->firstOrFail();

            # 删除原有绑定关系
            PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::with([])->where('repair_base_breakdown_order_temp_entire_instance_id', $id)->delete();

            # 创建新绑定关系
            $pivot = [];
            foreach (request('breakdown_type_ids') as $breakdown_type_id) {
                $pivot[] = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'repair_base_breakdown_order_temp_entire_instance_id' => $id,
                    'breakdown_type_id' => $breakdown_type_id,
                    'type' => request('type'),
                ];
            }
            PivotBreakdownOrderTempEntireInstanceAndBreakdownTypes::with([])->insert($pivot);

            return response()->json(['message' => '保存成功']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
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
        //
    }
}
