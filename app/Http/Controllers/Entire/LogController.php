<?php

namespace App\Http\Controllers\Entire;

use App\Http\Controllers\Controller;
use App\Model\EntireInstanceLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            EntireInstanceLog::with([])->create([
                'name' => '补录入所故障描述',
                'description' => $request->get('in_warehouse_breakdown_explain'),
                'entire_instance_identity_code' => $request->get('entire_instance_identity_code'),
                'type' => 0,
                'operator_id' => session('account.id'),
                'station_unique_code' => '',
            ]);
            return response()->json(['message' => '记录日志成功']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 补录日志
     * @param Request $request
     * @param string $identityCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postLog(Request $request, string $identityCode)
    {
        $types = [
            '设备出厂' => 0,
            '设备入所' => 1,
            '设备出所' => 1,
            '设备入库' => 0,
            '开始检修' => 2,
            '检修完成' => 2,
            '验收完成' => 2,
        ];

        try {
            $entire_instance_log = EntireInstanceLog::with([])
                ->insert([
                    'created_at' => $request->get('submitted_at'),
                    'updated_at' => $request->get('submitted_at'),
                    'description' => $request->get('description'),
                    'name' => $request->get('type'),
                    'type' => $types[$request->get('type')] ?? 0,
                    'entire_instance_identity_code' => $identityCode,
                    'operator_id' => session('account.id'),
                    'station_unique_code' => $request->get('station_unique_code'),
                ]);
            return response()->json(['message' => '添加成功', 'data' => $entire_instance_log]);
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
