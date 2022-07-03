<?php

namespace App\Console\Commands;

use App\Model\EntireInstance;
use Illuminate\Console\Command;

class Workshop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workshop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据车站将车间补全';

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
        ini_set('memory_limit', '-1');

        $statisticsRootDir = storage_path('app/basicInfo');
        $stations = [];
        foreach (json_decode(file_get_contents("{$statisticsRootDir}/stations.json"), true) as $sceneWorkshopUniqueCode => $sceneWorkshop) {
            foreach ($sceneWorkshop['subs'] as $stationUniqueCode => $station) {
                $stations[$station['name']] = $station['parent']['name'];
            }
        }

        EntireInstance::with([])
            ->orderBy('id')
            ->chunk(5, function ($entireInstances) use ($stations) {
                foreach ($entireInstances as $entireInstance) {
                    $entireInstance->fill(['maintain_workshop_name' => $stations[$entireInstance->maintain_station_name ?? ''] ?? ''])->save();
                    $this->info($entireInstance->id);
                }
            });
        $this->info('执行成功');
        return 0;
    }
}
