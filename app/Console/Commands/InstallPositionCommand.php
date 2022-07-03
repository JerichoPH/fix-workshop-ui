<?php

namespace App\Console\Commands;

use App\Model\Install\InstallPlatoon;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallRoom;
use App\Model\Install\InstallShelf;
use App\Model\Install\InstallTier;
use App\Model\Maintain;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;
use Jericho\FileSystem;

class InstallPositionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip:{type} {station_name?} {installRoomType?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import install positions from excel';

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
        $type = $this->argument("type");

        switch ($type) {
            case "ife":
                $this->inputFormExcel();
                break;
            case "delete":
                $this->delete();
                break;
        }

        return 0;
    }

    /**
     * @return int
     */
    private function inputFormExcel(): int
    {
        $this->comment('import install positions begin...');
        try {
            // check install room type
            $install_room_type = $this->argument('installRoomType');
            $install_room_type = array_flip(InstallRoom::$TYPES)[$install_room_type] ?? '';
            if (!$install_room_type) {
                $this->error("the install room type: {$install_room_type} is doesn't exists");
                return 1;
            }

            // check station is exists
            $station_name = $this->argument('station_name');
            $station = Maintain::with([])->where('name', $station_name)->where('type', 'STATION')->first();
            if (!$station) {
                $this->error("the station: {$station_name} is doesn't exists");
                return 2;
            }

            // check excel file is exists
            $fs = FileSystem::init(storage_path("{$station_name}.xls"));
            if (!is_file($fs->current())) {
                $this->error("excel: {$station_name}.xls is doesn't exists");
                return 3;
            }

            $excel = ExcelReadHelper::FROM_STORAGE($fs->current())
                ->originRow(1);
            $sheet_names = $excel->php_excel->getSheetNames();
            $this->info('read excel correctly');

            foreach ($sheet_names as $sheet_name) {
                $sheet = ExcelReadHelper::NEW_FROM_STORAGE($fs->current())->originRow(1)->withSheetName($sheet_name);

                list($install_platoon_name, $install_shelf_name) = explode('-', $sheet_name);

                // check room exists
                $install_room = InstallRoom::with([])->where('station_unique_code', $station->unique_code)->where('type', $install_room_type)->first();
                if (!$install_room) {
                    $this->error("room:{" . $this->argument('installRoomType') . "} ({$station->name}) is doesn't exists");
                    return 4;
                }

                // check platoon exists
                $install_platoon = InstallPlatoon::with([])->where('install_room_unique_code', $install_room->unique_code)->where('name', $install_platoon_name)->first();
                if (!$install_platoon) {
                    $this->error("platoon:{$install_platoon_name} is doesn't exists");
                    return 5;
                }

                // check shelf repeat
                $install_shelf = DB::table('install_shelves as is')
                    ->select(['ip.name as ip_name'])
                    ->join(DB::raw('install_platoons ip'), 'ip.unique_code', '=', 'is.install_platoon_unique_code')
                    ->join(DB::raw('install_rooms ir'), 'ir.unique_code', '=', 'ip.install_room_unique_code')
                    ->join(DB::raw('stations s'), 's.unique_code', '=', 'ir.station_unique_code')
                    ->where('s.unique_code', $station->name)
                    ->where('is.name', $install_shelf_name)
                    ->first();
                if ($install_shelf) {
                    $this->error("shelf:{$install_shelf_name} ({$station->name}) is already exists");
                    return 6;
                }

                // create shelf
                $install_shelf = InstallShelf::with([])->create([
                    'name' => rtrim($install_shelf_name, '柜') . '柜',
                    'unique_code' => InstallShelf::generateUniqueCode($install_platoon->unique_code),
                    'install_platoon_unique_code' => $install_platoon->unique_code,
                ]);
                $this->info("create shelf ({$station->name}-{$install_platoon_name}-{$install_shelf_name} is correctly");

                // create tiers and positions
                foreach ($sheet['success'] as $row_data) {
                    list($install_tier_name, $install_position_count) = $row_data;
                    $install_tier_name = strval($install_tier_name);
                    $install_position_count = intval($install_position_count);
                    // if ($install_tier_name === '' || $install_position_count <= 0) {
                    //     continue;  // if doesn't has tier name or position count then continue;
                    // }

                    // create tier
                    $install_tier = InstallTier::with([])->create([
                        'name' => $install_tier_name,
                        'unique_code' => InstallTier::generateUniqueCode($install_shelf->unique_code),
                        'install_shelf_unique_code' => $install_shelf->unique_code,
                    ]);
                    if ($install_tier) {
                        $this->info("create tier ({$station->name}-{$install_platoon_name}-{$install_shelf_name}-{$install_tier_name}) is correctly");
                    } else {
                        $this->comment("create tier ({$station->name}-{$install_platoon_name}-{$install_shelf_name}-{$install_tier_name}) is fail");
                    }

                    // create positions
                    $new_install_positions = InstallPosition::generateUniqueCodes($install_tier->unique_code, $install_position_count);
                    foreach ($new_install_positions as $new_install_position) {
                        ['unique_code' => $unique_code, 'name' => $name] = $new_install_position;
                        InstallPosition::with([])->create([
                            'name' => $name,
                            'unique_code' => $unique_code,
                            'install_tier_unique_code' => $install_tier->unique_code,
                        ]);
                    }
                }
            }
            return 0;
        } catch (Exception $e) {
            dd([
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                // $e->getTrace(),
            ]);
        }
    }

    private function delete(): void
    {
        $install_room_unique_code = $this->argument("station_name");

        InstallPlatoon::with([
            "WithInstallShelves",
            "WithInstallShelves.WithInstallTiers",
            "WithInstallShelves.WithInstallTiers.WithInstallPositions",
        ])
            ->where("install_room_unique_code", $install_room_unique_code)
            ->get()
            ->each(function ($install_platoon) {
                $install_platoon->WithInstallShelves->each(function ($install_shelf) {
                    if ($install_shelf->WithInstallTiers) {
                        $install_shelf->WithInstallTiers->each(function ($install_tier) {
                            if ($install_tier) {
                                $install_tier->WithInstallPositions->each(function ($install_position) {
                                    $install_position->delete();
                                });
                            }
                            $install_tier->delete();
                        });
                    }

                    $install_shelf->delete();
                });
                $install_platoon->delete();
            });
    }
}
