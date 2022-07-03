<?php

namespace App\Console\Commands;

use App\Model\EntireInstance;
use App\Model\PivotInDeviceAndOutDevice;
use Curl\Curl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\BadRequestException;

class HHAPI6Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'HHAPI6';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步设备与器材绑定关系';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            DB::transaction(function () {
                # Q010901B05200004933,Q010901B05200004940

                # 同步鲘门
                $curl = new Curl();
                $curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4');
                $curl->get("http://10.175.14.212:82/SPR/RewController/findEquByStationName.do", [
                    'vcStationName' => '鲘门',
                ]);
                if ($curl->error) dd([$curl->errorCode], $curl->errorMessage);
                $response1 = json_decode($curl->response, true);
                foreach ($response1['content'] as $item) {
                    if (empty($item['qcCode'])) continue;  # 跳过没有绑定的设备
                    EntireInstance::with([])
                        ->whereIn('identity_code', explode(',', $item['qcCode']))
                        ->update([
                            'updated_at' => date('Y-m-d H:i:s'),
                            'bind_device_code' => $item['vcEquNum'],  # 绑定设备编号
                            'bind_device_type_code' => $item['vcTypeId'],  # 绑定设备类型代码
                            'bind_device_type_name' => $item['typeName'],  # 绑定设备类型名称
                            'bind_crossroad_number' => $item['vcEquUseName'],  # 绑定设备道岔名称
                            'bind_crossroad_id' => '',  # 绑定设备道岔编号（暂无）
                            'bind_station_name' => $item['stationName'],  # 绑定设备所属车站
                            'bind_station_code' => '',  # 绑定设备所属车站代码
                        ]);
                }

                # 同步泰美
                $curl = new Curl();
                $curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4');
                $curl->get("http://10.175.14.212:82/SPR/RewController/findEquByStationName.do", [
                    'vcStationName' => '泰美',
                ]);
                if ($curl->error) dd([$curl->errorCode], $curl->errorMessage);
                $response2 = json_decode($curl->response, true);
                foreach ($response2['content'] as $item) {
                    if (empty($item['qcCode'])) continue;  # 跳过没有绑定的设备
                    EntireInstance::with([])
                        ->whereIn('identity_code', explode(',', $item['qcCode']))
                        ->update([
                            'updated_at' => date('Y-m-d H:i:s'),
                            'bind_device_code' => $item['vcEquNum'],  # 绑定设备编号
                            'bind_device_type_code' => $item['vcTypeId'],  # 绑定设备类型代码
                            'bind_device_type_name' => $item['typeName'],  # 绑定设备类型名称
                            'bind_crossroad_number' => $item['vcEquUseName'],  # 绑定设备道岔名称
                            'bind_crossroad_id' => '',  # 绑定设备道岔编号（暂无）
                            'bind_station_name' => $item['stationName'],  # 绑定设备所属车站
                            'bind_station_code' => '',  # 绑定设备所属车站代码
                        ]);
                }
            });
        } catch (BadRequestException $e) {
            dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return response()->json(['message' => '网络链接失败'], 500);
        } catch (\Exception $e) {
            dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
