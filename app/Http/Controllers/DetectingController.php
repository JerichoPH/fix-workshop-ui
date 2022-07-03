<?php

namespace App\Http\Controllers;

use App\Facades\Detecting;
use App\Model\FixWorkflowDetectingErrorLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jericho\TextHelper;

class DetectingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//        Detecting::appearMeasurement_ALX($json);
        return view('Detecting.index');
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
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $data = TextHelper::parseJson($request->get('data'));
        if (empty($data)) {
            Log::info('data-------------', []);
            return response()->json([]);
        } else {
            Log::info('data-------------', $data);
        }

        $header = $data['header'];
        if (!array_key_exists('body', $data)) return response()->json($data);
        $factories = DB::table('factories')->where('deleted_at', null)->pluck('name', 'unique_code')->toArray();
        try {
            $config = config('detecting');
            $organizationCode = env('ORGANIZATION_CODE');
            // $platform = array_flip($factories)[$header['platform']];
            $platform = 'P0039';
            $func = strtoupper($config[$platform][$organizationCode]['func']) . '_' . env('ORGANIZATION_CODE');  # ALX_B049
            $result = Detecting::$func($data, $config[$platform][$organizationCode]);
            return response()->json($result);
        } catch (ModelNotFoundException $exception) {
            $fixWorkflowDetectingErrorLog = new FixWorkflowDetectingErrorLog;
            $entireInstanceIdentityCode = DB::table('entire_instances')->where('serial_number', $data['header']['条码编号'])->first(['identity_code']);
            $entireInstanceIdentityCode = $entireInstanceIdentityCode ? $entireInstanceIdentityCode->identity_code : null;
            $fixWorkflowDetectingErrorLog->fill([
                // 'platform' => array_flip($factories)[$data['header']['platform']],
                'platform' => 'P0039',
                'organization_code' => env('ORGANIZATION_CODE'),
                'testing_device_id' => $data['header']['testing_device_ID'],
                'entire_instance_serial_number' => $data['header']['条码编号'],
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'error_description' => '数据不存在：' . substr($exception->getMessage(), 0, 200) . ' : ' . $exception->getFile() . ' : ' . $exception->getLine(),
                'detecting_data' => TextHelper::toJson($data),
            ])->saveOrFail();
            return response()->make(env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在', 404);
        } catch (\Exception $exception) {
            $entireInstanceIdentityCode = DB::table('entire_instances')->where('serial_number', $data['header']['条码编号'])->first(['identity_code']);
            $entireInstanceIdentityCode = $entireInstanceIdentityCode ? $entireInstanceIdentityCode->identity_code : null;
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            $fixWorkflowDetectingErrorLog = new FixWorkflowDetectingErrorLog;
            $fixWorkflowDetectingErrorLog->fill([
                // 'platform' => array_flip($factories)[$data['header']['platform']],
                'platform' => 'P0039',
                'organization_code' => env('ORGANIZATION_CODE'),
                'testing_device_id' => $data['header']['testing_device_ID'],
                'entire_instance_serial_number' => $data['header']['条码编号'],
                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                'error_description' => '意外错误：' . substr($exception->getMessage(), 0, 200) . ' : ' . $exception->getFile() . ' : ' . $exception->getLine(),
                'detecting_data' => TextHelper::toJson($data),
            ])->saveOrFail();
            return response()->make(env('APP_DEBUG') ? "{$eMsg}<br>{$eFile}<br>{$eLine}" : "意外错误", 500);
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
