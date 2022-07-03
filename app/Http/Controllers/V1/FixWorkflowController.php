<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;

class FixWorkflowController extends Controller
{
    use Helpers;

    private $_isCycle = [
        '周期修',
        '状态修'
    ];

    final public function show(string $serial_number)
    {
        try {
            $getBuilder = function (string $fixWorkflowSerialNumber, string $type): Builder {
                return DB::table('fix_workflow_processes as fwp')
                    ->select([
                        'm.key as key',
                        'm.unit as unit',
                        'fwr.measured_value as measured_value',
                        'a.nickname',
                        'fwp.type',
                    ])
                    ->join(DB::raw('accounts a'),'a.id','=','fwp.processor_id')
                    ->join(DB::raw('fix_workflow_records fwr'), 'fwr.fix_workflow_process_serial_number', '=', 'fwp.serial_number')
                    ->join(DB::raw('measurements m'), 'm.identity_code', '=', 'fwr.measurement_identity_code')
                    ->where('fwp.deleted_at', null)
                    ->where('fwp.fix_workflow_serial_number', $fixWorkflowSerialNumber)
                    ->where('fwp.type', $type);
            };

            $lastFixWorkflowRecordEntire = $getBuilder($serial_number, 'ENTIRE')->get()->toArray();
            $lastFixWorkflowRecordPart = $getBuilder($serial_number, 'PART')->get()->toArray();

            $lastFixWorkflowRecord = [];
            foreach ($lastFixWorkflowRecordEntire as $item) array_push($lastFixWorkflowRecord, $item);
            foreach ($lastFixWorkflowRecordPart as $item) array_push($lastFixWorkflowRecord, $item);

            return HttpResponseHelper::data($lastFixWorkflowRecord);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
//            return HttpResponseHelper::data("{$msg}\r\n{$line}\r\n{$file}");
            return HttpResponseHelper::data("{$msg}");
        }
    }

    public function entireInstance()
    {

    }
}
