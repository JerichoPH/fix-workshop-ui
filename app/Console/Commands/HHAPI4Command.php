<?php

namespace App\Console\Commands;

use App\Facades\BreakdownLogFacade;
use Curl\Curl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\BadRequestException;

class HHAPI4Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'HHAPI4';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步现场故障信息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    final public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    final public function handle()
    {
        try {
            DB::transaction(function () {
                $insert = function ($response) {
                    if (!is_dir(storage_path("HH"))) mkdir(storage_path("HH"));
                    $breakdownLogs = [];  # 现场故障描述
                    $entireInstanceLogs = [];  # 设备日志
                    $breakdownLogsVCIds = is_file(storage_path("HH/breakdownLogsVCIds.json"))
                        ? json_decode(file_get_contents(storage_path("HH/breakdownLogsVCIds.json")), true)
                        : [];  # 设备故障时间对照表

                    foreach ($response as $item) {
                        # 如果相同设备存在相同时间则跳过循环
                        if (in_array($item['vcId'], $breakdownLogsVCIds)) continue;

                        /**
                         * 原样记载故障类型
                         * @param string $identityCode 器材唯一编号
                         * @param string $explain 故障描述
                         * @param string $submittedAt 上报时间
                         * @param string $crossroadNumber 道岔名称
                         * @param string $submitterName 上报人
                         * @param string $stationName 车站名称
                         * @param string $locationCode 组合位置
                         * @return bool
                         */
                        BreakdownLogFacade::createStationAsOriginal(
                            $item['vcEquPart'],
                            $item['vcDesc'],
                            $item['dtHappen'],
                            $item['vcBelongName'],
                            $item['vcAdd'] ?? ''
                        );

                        # 记录设备故障唯一编号避免重复
                        $breakdownLogsVCIds[] = $item['vcId'];
                    }
                    # 保存故障唯一编号
                    file_put_contents(storage_path('HH/breakdownLogsVCIds.json'), json_encode($breakdownLogsVCIds, 256));
                };

                foreach (['鲘门', '泰美'] as $item) {
                    # 同步鲘门
                    $curl = new Curl();
                    $curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4');
                    $curl->get("http://10.175.14.212:82/SPR/RewController/findQcfxByStationName.do", [
                        'vcStationName' => $item,
                    ]);
                    if ($curl->error) dd([$curl->errorCode], $curl->errorMessage);
                    $data = json_decode($curl->response, true);
                    if (array_key_exists('content', $data)) {
                        file_put_contents(storage_path("HH/{$item}.json"), $curl->response);
                        $insert($data['content']);
                    }
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
