<?php

namespace App\Http\Controllers\V1;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;

class PointSwitchController extends Controller
{
    /**
     * 通过厂编号获取新购转辙机
     * @param string $factory_device_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function newIn(string $factory_device_code)
    {
        $entire_instance = DB::table('entire_instances as ei')
            ->select(['ei.identity_code as entire_identity_code', 'pi.identity_code as part_identity_code'])
            ->join(DB::raw('part_instances as pi'), 'pi.entire_instance_identity_code', '=', 'ei.identity_code')
            ->where('ei.deleted_at', null)
            ->where('ei.category_unique_code', 'S03')
            ->where('ei.status', 'BUY_IN')
            ->where('ei.factory_device_code', $factory_device_code)
            ->first();
        if (!$entire_instance) return HttpResponseHelper::errorEmpty();

        $entire_instance->epc = CodeFacade::identityCodeToHex($entire_instance->entire_identity_code);
        return HttpResponseHelper::data($entire_instance);
    }

    /**
     * 修改epc且检修入所
     * @param string $epc
     * @return \Illuminate\Http\JsonResponse
     */
    final public function updateEpcAndFixing(string $epc)
    {
//        $identity_code = Code::hexToIdentityCode($epc);
//        DB::transaction(function () use ($identity_code) {
//            DB::table('entire_instances as ei')->where('identity_code', $identity_code)->update(['status' => 'FIXING']);
//            DB::table('part_instances as pi')->where('entire_instance_identity_code', $identity_code)->update(['status' => 'FIXING']);
//        });
        return HttpResponseHelper::created('修改成功');
    }

    /**
     * 查询设备（更换部件）
     * @return \Illuminate\Http\JsonResponse
     */
    final public function queryForChangePart()
    {
        try {
            if (request('code', null)) {
                # 通过通用code查询
                $identity_code = EntireInstanceFacade::getEntireInstanceIdentityCodeByCodeForPda(request('code'));
                $entire_instance = DB::table('entire_instances as ei')
                    ->select([
                        'ei.factory_device_code',
                        'ei.identity_code as entire_identity_code',
                        'pi.identity_code as part_identity_code',
                        'pi.part_model_unique_code'
                    ])
                    ->join(DB::raw('part_instances as pi'), 'pi.entire_instance_identity_code', '=', 'ei.identity_code')
                    ->where('ei.deleted_at', null)
                    ->where('ei.identity_code', $identity_code)
                    ->first();
            } elseif (request('steel_seal_code', null)) {
                # 通过钢印号查询
                switch (request('instance_type')) {
                    case '1':
                        # 通过整件钢印号
                        $entire_instance = DB::table('entire_instances as ei')
                            ->select([
                                'ei.factory_device_code',
                                'ei.identity_code as entire_identity_code',
                                'pi.identity_code as part_identity_code',
                                'pi.part_model_unique_code'
                            ])
                            ->join(DB::raw('part_instances as pi'), 'pi.entire_instance_identity_code', '=', 'ei.identity_code')
                            ->where('ei.deleted_at', null)
                            ->where('pi.deleted_at', null)
                            ->where('ei.identity_code', request('steel_seal_code'))
                            ->first();
                        break;
                    case '2':
                        # 通过部件钢印号
                        $entire_instance = DB::table('part_instances as pi')
                            ->select([
                                'ei.factory_device_code',
                                'ei.identity_code as entire_identity_code',
                                'pi.identity_code as part_identity_code',
                                'pi.part_model_unique_code'
                            ])
                            ->join(DB::raw('entire_instances as ei'), 'ei.identity_code', '=', 'pi.entire_instance_identity_code')
                            ->where('ei.deleted_at', null)
                            ->where('pi.deleted_at', null)
                            ->where('pi.identity_code', request('steel_seal_code'))
                            ->first();
                        break;
                    default:
                        return HttpResponseHelper::errorForbidden('编号格式错误');
                        break;
                }
            } else {
                return HttpResponseHelper::errorForbidden('编号类型错误');
            }
            if(empty($entire_instance) || is_null($entire_instance)) return HttpResponseHelper::errorEmpty('没有找到设备');

            $part_instances = DB::table('part_instances as pi')
                ->where('pi.deleted_at', null)
                ->where('pi.part_model_unique_code', $entire_instance->part_model_unique_code)
                ->where('pi.entire_instance_identity_code',null)
                ->whereIn('pi.status', ['FIXED'])
                ->pluck('identity_code');

            return HttpResponseHelper::data([
                'entire_instance' => $entire_instance,
                'part_instances' => $part_instances,
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $line = $e->getLine();
            $file = $e->getFile();
//            return HttpResponseHelper::errorForbidden("{$msg}\r\n{$line}\r\n{$file}");
            return HttpResponseHelper::errorForbidden("{$msg}");
        }
    }

    /**
     * 更换部件
     * @param string $entire_identity_code
     * @param string $part_identity_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function changePart(string $entire_identity_code, string $part_identity_code)
    {
        $entire_instance = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('identity_code', $entire_identity_code)->first(['id']);
        $part_instance = DB::table('part_instances as pi')->where('pi.deleted_at', null)->where('identity_code', $part_identity_code)->first(['id']);
        if (!$entire_instance) return HttpResponseHelper::errorEmpty('整件不存在');
        if (!$part_instance) return HttpResponseHelper::errorEmpty('部件不存在');

        DB::transaction(function () use ($entire_identity_code, $part_identity_code, $part_instance) {
            DB::table('part_instances as pi')->where('pi.identity_code', $part_identity_code)->update(['status' => 'FIXING', 'entire_instance_identity_code' => null]);
            DB::table('part_instances as pi')->where('pi.id', $part_instance->id)->update(['entire_instance_identity_code' => $entire_identity_code]);
        });

        return HttpResponseHelper::created('改绑成功');
    }
}
