<?php

namespace App\Http\Controllers\V1;

use App\Facades\CodeFacade;
use App\Facades\DingResponseFacade;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class EntireModelController extends Controller
{
    use Helpers;

    /**
     * 备品备件查询
     * @return \Illuminate\Http\JsonResponse
     */
    final public function standby()
    {
        switch (request('type')) {
            case 1:
            default:
                # 通过厂编号获取数据
                $where = 'factory_device_code';
                break;
            case 2:
                # 通过所编号获取数据
                $where = 'serial_number';
                break;
            case 3:
                # 通过唯一编号获取数据
                $where = 'identity_code';
                break;
            case 4:
                #通过RFID TID获取数据
                $where = 'rfid_code';
                break;
            case 5:
                # 通过RFID EPC获取数据
                $where = 'rfid_epc';
                break;
        }

        switch (strlen(request('code'))) {
            case 14:
            case 19:
                # 360文件标准唯一编号
                $identityCode = request('code');
                break;
            case 24:
                $firstCode = substr(request('code'), 0, 4);
                if ($firstCode == '130E') {
                    # 转辙机十六进制唯一编号
                    $identityCode = CodeFacade::hexToIdentityCode(request('code'));
                } else {
                    $tmp = DB::table('entire_instances as ei')->where('rfid_code', request('code'))->first(['identity_code']);
                    if (!$tmp) return HttpResponseHelper::errorEmpty('设备不存在');
                    $identityCode = $tmp->identity_code;
                }
                break;
        }
        $oldEntireInstance = DB::table('entire_instances as ei')
            ->where($where, $identityCode)
            ->first(['ei.maintain_station_name', 'ei.entire_model_unique_code']);

        if ($oldEntireInstance) {
            # 搜索备品
            $entireInstancesWithInstalling = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.category_name',
                    'em.name as entire_model_name',
                ])
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                ->where('ei.status', 'INSTALLING')
                ->where('ei.maintain_station_name', $oldEntireInstance->maintain_station_name)
                ->where('ei.entire_model_unique_code', $oldEntireInstance->entire_model_unique_code)
                ->get();

            $entireInstancesWithFixed = DB::table('entire_instances as ei')
                ->select([
                    'ei.identity_code',
                    'ei.category_name',
                    'em.name as entire_model_name',
                ])
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                ->where('ei.status', 'FIXED')
                ->where('ei.entire_model_unique_code', $oldEntireInstance->entire_model_unique_code)
                ->get();
            return HttpResponseHelper::data([
                'installing' => $entireInstancesWithInstalling,
                'fixed' => $entireInstancesWithFixed,
            ]);
        } else {
            return HttpResponseHelper::errorEmpty("搜索结果不存在，类型：" . request('type'));
        }
    }

    /**
     * 设备上道（通过备品）
     */
    final public function installWithStandby()
    {
        $request = TextHelper::parseJson(@file_get_contents('php://input'));

        switch ($request['type']) {
            case 1:
            default:
                # 通过厂编号获取数据
                $where = 'factory_device_code';
                break;
            case 2:
                # 通过所编号获取数据
                $where = 'serial_number';
                break;
            case 3:
                # 通过唯一编号获取数据
                $where = 'identity_code';
                break;
            case 4:
                #通过RFID TID获取数据
                $where = 'rfid_code';
                break;
            case 5:
                # 通过RFID EPC获取数据
                $where = 'rfid_epc';
                break;
        }

        # 查询老设备
        $oldEntireInstance = DB::table('entire_instances')
            ->select([
                'identity_code',
                'maintain_station_name',
                'maintain_location_code',
                'to_direction',
                'crossroad_number',
                'traction',
                'line_name',
                'open_direction',
                'said_rod',
                'rfid_code',
            ])
            ->where('deleted_at', null)
            ->where($where, $request['old_code'])
            ->first();
        if (!$oldEntireInstance) return HttpResponseHelper::errorEmpty('查询为空或设备状态错误');

        DB::transaction(function () use ($oldEntireInstance, $where, $request) {
            # 修改老设备状态
            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->where('identity_code', $oldEntireInstance->identity_code)
                ->update(['status' => 'FIXED']);

            # 修改新设备
            DB::table('entire_instances')
                ->where('deleted_at', null)
                ->where($where, $request['new_code'])
                ->update([
                    'status' => 'INSTALLED',
                    'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                    'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                    'to_direction' => $oldEntireInstance->to_direction,
                    'crossroad_number' => $oldEntireInstance->crossroad_number,
                    'traction' => $oldEntireInstance->traction,
                    'line_name' => $oldEntireInstance->line_name,
                    'open_direction' => $oldEntireInstance->open_direction,
                    'said_rod' => $oldEntireInstance->said_rod,
                ]);
        });

        return HttpResponseHelper::created('上道成功');
    }
}
