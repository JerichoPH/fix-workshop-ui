<?php

namespace App\Http\Controllers\V1;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceFacade;
use App\Services\EntireInstanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;
use PHPUnit\Exception;

class CycleFixController extends Controller
{
    /**
     * 获取月份列表
     * @return \Illuminate\Http\JsonResponse
     */
    final public function months()
    {
        $now = Carbon::now();
        $currentYear = $now->year;
        $nextYear = $now->addYear()->year;

        $dates = TextHelper::parseJson(file_get_contents(storage_path("app/周期修/dateList.json")));

        if (!$dates) return HttpResponseHelper::errorEmpty('周期修数据不存在');

        $ret = [];
        foreach ($dates as $date) {
            $year = explode('-', $date)[0];
            if ($year == $currentYear || $year == $nextYear) $ret[] = $date;
        }

        return HttpResponseHelper::data($ret);
    }

    /**
     * 通过设备编号获取需要轮修的位置
     * @param string $code
     * @param bool $return_sub_model_name
     * @return \Illuminate\Http\JsonResponse|array
     */
    final public function locations(string $code, bool $return_sub_model_name = false)
    {
        try {
            if (request('date')) {
                list($year, $month) = explode('-', request('date'));
            } else {
                $now = Carbon::now();
                $now->addMonth(2);
                $year = $now->year;
                $month = str_pad($now->month, 2, '0', STR_PAD_LEFT);
            }

            $identity_code = EntireInstanceFacade::getEntireInstanceIdentityCodeByCodeForPda($code);

            switch (substr($identity_code, 0, '1')) {
                case 'S':
                    $sub_model_name = DB::table('part_instances as pi')
                        ->select(['pm.name'])
                        ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'pi.part_model_unique_code')
                        ->where('pi.entire_instance_identity_code', $identity_code)
                        ->first();
                    break;
                case 'Q':
                    $sub_model_name = DB::table('entire_instances as ei')
                        ->select(['em.name'])
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                        ->where('ei.identity_code', $identity_code)
                        ->first();
                    break;
                default:
                    return HttpResponseHelper::errorForbidden('编号格式错误');
                    break;
            }

            if (!$sub_model_name) return HttpResponseHelper::errorEmpty('获取设备类型失败');
            $sub_model_name = $sub_model_name->name;

            $locations = TextHelper::parseJson(file_get_contents(storage_path("app/周期修/{$year}/{$year}-{$month}/位置码-型号和子类-车站.json")));
            # 格式化
            $ret = [];
            foreach ($locations[$sub_model_name] as $station_name => $item) foreach ($item as $identity_code => $value) $ret[] = [
                'model_name' => $sub_model_name,
                'station_name' => $station_name,
                'identity_code' => $identity_code,
                'location_code' => $value['location_code'],
                'print_sum' => $value['print_sum']
            ];

            return $return_sub_model_name ? [$sub_model_name, $identity_code] : HttpResponseHelper::data($ret);
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 打印条码 并记录打印次数
     * @param Request $request
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function print(Request $request, string $code)
    {
        if (!$request->get('date', null)) return HttpResponseHelper::errorForbidden('日期不能为空');
        list($year, $month) = explode('-', $request->get('date'));

        list($sub_model_name, $entire_identity_code) = self::locations($code, true);
        $source_file = TextHelper::parseJson(file_get_contents(storage_path("app/周期修/{$year}/{$year}-{$month}/位置码-型号和子类-车站.json")));

        foreach ($source_file[$sub_model_name] as $station_name => $item)
            foreach ($item as $identity_code => $value)
                if ($identity_code == $request->get('location_entire_instance_identity_code')) {
                    # 同步位置信息
                    $source_file[$sub_model_name][$station_name][$identity_code]['print_sum'] += 1;
                    DB::table('entire_instances as ei')->where('identity_code', $entire_identity_code)->update(['maintain_location_code' => $value['location_code'], 'maintain_station_name' => $station_name]);
                }

        $save_file_ret = file_put_contents(storage_path("app/周期修/{$year}/{$year}-{$month}/位置码-型号和子类-车站.json"), TextHelper::toJson($source_file));
        if ($save_file_ret > 0) return HttpResponseHelper::created('保存成功');
        return HttpResponseHelper::errorForbidden('保存失败');
    }
}
