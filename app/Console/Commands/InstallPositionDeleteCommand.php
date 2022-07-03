<?php

namespace App\Console\Commands;

use App\Facades\CommonFacade;
use App\Model\Install\InstallPlatoon;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallRoom;
use App\Model\Install\InstallShelf;
use App\Model\Install\InstallTier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstallPositionDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip:del {station_name} {room_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            $station_name = $this->argument('station_name');
            $room_name = $this->argument('room_name');
            $room_type_code = collect(InstallRoom::$TYPES)->flip()->get($room_name, '') ?? '';
            if (!$room_type_code) dd("机房类型名称错误：{$room_name}");

            $station = DB::table('maintains')->where('type', 'STATION')->where('name', $station_name)->first();
            if (!$station) dd("车站：{$station_name}不存在");

            $install_rooms = InstallRoom::with([])->where('station_unique_code', $station->unique_code)->where('type', $room_type_code)->first();
            if (!$install_rooms) dd("机房：{$room_name}不存在");

            $install_platoons = InstallPlatoon::with([])->where('install_room_unique_code', $install_rooms->unique_code)->get();
            $install_shelves = InstallShelf::with([])->whereIn('install_platoon_unique_code', $install_platoons->pluck('unique_code')->toArray())->get();
            $install_tiers = InstallTier::with([])->whereIn('install_shelf_unique_code', $install_shelves->pluck('unique_code')->toArray())->get();
            $install_positions = InstallPosition::with([])->whereIn('install_tier_unique_code', $install_tiers->pluck('unique_code')->toArray())->get();
            InstallPlatoon::with([])->whereIn('id', $install_platoons->pluck('id'))->forceDelete();
            $this->info("排删除：{$install_platoons->count()}。");
            InstallShelf::with([])->whereIn('id', $install_shelves->pluck('id'))->forceDelete();
            $this->info("架删除：{$install_shelves->count()}。");
            InstallTier::with([])->whereIn('id', $install_tiers->pluck('id'))->forceDelete();
            $this->comment("层删除：{$install_tiers->count()}。");
            InstallPosition::with([])->whereIn('id', $install_positions->pluck('id'))->forceDelete();
            $this->comment("位删除：{$install_positions->count()}。");

        } catch (Throwable $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
