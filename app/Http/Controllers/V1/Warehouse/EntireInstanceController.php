<?php

namespace App\Http\Controllers\V1\Warehouse;

use App\Facades\CodeFacade;
use App\Facades\FixWorkflowFacade;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class EntireInstanceController extends Controller
{
    use Helpers;

    /**
     * Display a listing of the resource.
     *
     * @return \Dingo\Api\Http\Response
     */
    final public function index()
    {
        switch (request('type', 'withoutRFID')) {
            case 'withoutRFID':
                $ret = DB::table('entire_instances')
                    ->where('deleted_at', null)
                    ->where('rfid_code', null)
                    ->select(['factory_device_code', 'identity_code'])
                    ->paginate(request('pageSize', 50));
                return $this->response()->array($ret);
                break;
            default:
                return $this->response()->noContent();
                break;
        }
    }

    /**
     * 批量绑定rfid到设备
     * @param Request $request
     * @return mixed
     */
    final public function batchBindingRFIDWithIdentityCode(Request $request)
    {
        try {
            $req = TextHelper::parseJson($request->getContent());
            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->where('identity_code', $req['identityCode'])
//                ->update(['rfid_code' => $req['rfidCode']]);
            ->update(['rfid_code' => $req['rfidCode'],'status'=>'FIXED']);  # todo: 检测台接入之前，所有绑定TID设备都要改为成品

            FixWorkflowFacade::makeByEntireInstanceIdentityCode($req['identityCode']);

            return $this->response()->created();
        } catch (ModelNotFoundException $exception) {
            $errorMessage = '数据不存在：' . $exception->getMessage() . env('APP_DEBUG', false) ? ' : ' . $exception->getFile() . ' : ' . $exception->getLine() : '';
            $this->response()->error($errorMessage, 404);
        } catch (\Exception $exception) {
            $errorMessage = '意外错误：' . $exception->getMessage() . env('APP_DEBUG', false) ? ' : ' . $exception->getFile() . ' : ' . $exception->getLine() : '';
            $this->response()->error($errorMessage, $exception->getCode());
        }
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $entireInstances = DB::table('entire_instances')
                ->select([
                    'entire_instances.identity_code',
                    'entire_instances.factory_device_code',
                    'entire_instances.serial_number',
                    'entire_instances.rfid_code',
                    'entire_instances.maintain_station_name',
                    'entire_instances.maintain_location_code',
                    'entire_models.name as entire_model_name',
                ])
                ->join('entire_models', 'entire_models.unique_code', '=', 'entire_instances.entire_model_unique_code')
                ->where('entire_instances.deleted_at', null)
                ->where('entire_instances.' . request('type', 'factory_device_code'), $id)
                ->get();
            foreach ($entireInstances as $entireInstance) {
                $entireInstance->hex_identity_code = CodeFacade::identityCodeToHex($entireInstance->identity_code);
            }
            return $this->response()->array($entireInstances);
        } catch (ModelNotFoundException $exception) {
            $errorMessage = '数据不存在：' . $exception->getMessage() . env('APP_DEBUG', false) ? ' : ' . $exception->getFile() . ' : ' . $exception->getLine() : '';
            $this->response()->error($errorMessage, 404);
        } catch (\Exception $exception) {
            $errorMessage = '意外错误：' . $exception->getMessage() . env('APP_DEBUG', false) ? ' : ' . $exception->getFile() . ' : ' . $exception->getLine() : '';
            $this->response()->error($errorMessage, 500);
        }
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
