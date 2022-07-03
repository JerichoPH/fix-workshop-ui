<?php

namespace App\Http\Controllers\V1\Warehouse;

use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class ReportController extends Controller
{
    use Helpers;

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
     * 通过rfid提交批量扫码
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function rfid(Request $request)
    {
        try {
            $time = date('Y-m-d H:i:s');

            $success = [];
            $fail = [];

            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->whereIn('rfid_code', $request->get('rfid_codes'))
                ->pluck('rfid_code', 'identity_code')
                ->each(
                    function ($rfidCode, $identityCode)
                    use ($time, &$success, &$fail) {
                        if (DB::table('warehouse_batch_reports')->where('entire_instance_identity_code', $identityCode)->first() == null) {
                            # 批量导入表中不存在，写入数据库
                            $success[] = $rfidCode;
                            DB::table('warehouse_batch_reports')->insert([
                                'created_at' => $time,
                                'updated_at' => $time,
                                'entire_instance_identity_code' => $identityCode,
                                // 'serial_number' => $serialNumber
                            ]);
                        } else {
                            # 批量导入表中存在
                            $fail[] = $rfidCode;
                        }
                    }
                );

            $ret = [
                'success' => [
                    'items' => $success,
                    'count' => count($success),
                ],
                'fail' => [
                    'items' => $fail,
                    'count' => count($fail)
                ]
            ];
            return $this->response()->created(null, TextHelper::toJson($ret));
        } catch (\Exception $exception) {
            $this->response()->errorForbidden($exception->getMessage());
        }
    }

    /**
     * 通过流水号提交批量扫码
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function store(Request $request)
    {
        try {
            $time = date('Y-m-d H:i:s');

            $success = [];
            $fail = [];
            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->whereIn('serial_number', $request->get('serial_numbers'))
                ->pluck('serial_number', 'identity_code')
                ->each(
                    function ($serialNumber, $identityCode)
                    use ($time, &$success, &$fail) {
                        if (DB::table('warehouse_batch_reports')->where('entire_instance_identity_code', $identityCode)->first() == null) {
                            # 批量导入表中不存在，写入数据库
                            $success[] = $serialNumber;
                            DB::table('warehouse_batch_reports')->insert([
                                'created_at' => $time,
                                'updated_at' => $time,
                                'entire_instance_identity_code' => $identityCode,
//                                'serial_number' => $serialNumber
                            ]);
                        } else {
                            # 批量导入表中存在
                            $fail[] = $serialNumber;
                        }
                    }
                );

            $ret = [
                'success' => [
                    'items' => $success,
                    'count' => count($success),
                ],
                'fail' => [
                    'items' => $fail,
                    'count' => count($fail)
                ]
            ];
            return $this->response()->created(null, TextHelper::toJson($ret));
        } catch (\Exception $exception) {
            $this->response()->errorForbidden($exception->getMessage());
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
