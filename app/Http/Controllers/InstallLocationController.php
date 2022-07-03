<?php

namespace App\Http\Controllers;

use App\Exceptions\ForbiddenException;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\SyncFacade;
use App\Http\Requests\Install\InstallPlatoonStoreRequest;
use App\Http\Requests\Install\InstallPositionUpdateRequest;
use App\Http\Requests\Install\InstallRoomStoreRequest;
use App\Http\Requests\Install\InstallShelfStoreRequest;
use App\Model\EntireInstance;
use App\Model\Install\InstallPlatoon;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallRoom;
use App\Model\Install\InstallShelf;
use App\Model\Install\InstallTier;
use App\Model\Maintain;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\TextHelper;
use Jericho\ValidateHelper;
use Throwable;

class InstallLocationController extends Controller
{
    private $_currentTime = null;
    private $_installUniqueCode = null;
    private $_organizationCode = null;

    final public function __construct()
    {
        $this->_currentTime = date('Y-m-d H:i:s');
        $this->_installUniqueCode = env('INSTALL_UNIQUE_CODE');
        $this->_organizationCode = env('ORGANIZATION_CODE');
    }

    /**
     * 上道位置列表
     * @param Request $request
     * @return Factory|RedirectResponse|View
     */
    final public function index(Request $request)
    {
        try {
            $workshops = DB::table('maintains')->whereIn('type', ['SCENE_WORKSHOP', 'WORKSHOP'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->select('name', 'unique_code')->pluck('name', 'unique_code')->toArray();
            $workshop_unique_code = $request->get('workshop_unique_code', '');
            $station_unique_code = $request->get('station_unique_code', '');
            $install_platoon_unique_code = $request->get('install_platoon_unique_code', '');
            $install_shelf_unique_code = $request->get('install_shelf_unique_code', '');
            $install_tier_unique_code = $request->get('install_tier_unique_code', '');
            $locations = InstallTier::with(['WithInstallShelf'])
                ->when(
                    !empty($workshop_unique_code),
                    function ($query) use ($workshop_unique_code) {
                        return $query->whereHas('WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation.Parent', function ($q) use ($workshop_unique_code) {
                            $q->where('unique_code', $workshop_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($station_unique_code),
                    function ($query) use ($station_unique_code) {
                        return $query->whereHas('WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation', function ($q) use ($station_unique_code) {
                            $q->where('unique_code', $station_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($install_platoon_unique_code),
                    function ($query) use ($install_platoon_unique_code) {
                        return $query->whereHas('WithInstallShelf.WithInstallPlatoon', function ($q) use ($install_platoon_unique_code) {
                            $q->where('unique_code', $install_platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($install_shelf_unique_code),
                    function ($query) use ($install_shelf_unique_code) {
                        return $query->where('install_shelf_unique_code', $install_shelf_unique_code);
                    }
                )
                ->when(
                    !empty($install_tier_unique_code),
                    function ($query) use ($install_tier_unique_code) {
                        return $query->where('unique_code', $install_tier_unique_code);
                    }
                )
                ->paginate(100);

            return view('Install.Location.index', [
                'locations' => $locations,
                'workshops' => $workshops,
                'install_room_types_as_json' => json_encode(InstallRoom::$TYPES, 256),
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 上道位置对比
     * @param Request $request
     * @return Factory|Application|View
     */
    final public function GetTest(Request $request)
    {
        try {
            $scene_workshops = DB::table('maintains as sc')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->where("is_show", true)
                ->get();
            $stations = DB::table('maintains as s')
                ->where('s.type', 'STATION')
                ->where("s.parent_unique_code", "<>", "")
                ->where("s.is_show", true)
                ->get()
                ->groupBy('parent_unique_code');
            $install_positions = [];
            if (request('install_shelf_unique_code')) {
                $install_positions = InstallPosition::with(['WithInstallTier',])
                    ->where('install_tier_unique_code', 'like', request('install_shelf_unique_code') . '%')
                    ->get();
            }

            $installed_positions = [];
            if (request('station_unique_code') && !empty($install_positions)) {
                $station = DB::table('maintains as s')->where('s.deleted_at', null)->where('unique_code', request('station_unique_code'))->first();
                $installed_positions = EntireInstance::with(['InstallPosition',])
                    ->where('maintain_station_name', $station->name)
                    ->whereIn('maintain_location_code', $install_positions->pluck('unique_code'))
                    ->pluck('maintain_location_code')
                    ->toArray();
            }

            $install_shelf = InstallShelf::with([
                'WithInstallTiers',
                'WithInstallTiers.WithInstallPositions',
            ])
                ->where('unique_code', request('install_shelf_unique_code'))
                ->first();

            $installed_entire_instances = collect([]);
            if (!empty($install_shelf)) {
                $installed_entire_instances = EntireInstance::with([])
                    ->select(['model_unique_code', 'model_name', 'maintain_location_code', 'identity_code'])
                    ->where('maintain_location_code', 'like', $install_shelf->unique_code . '%')
                    ->get()
                    ->groupBy('maintain_location_code');
            }

            return view('Install.Location.test', [
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'install_positions' => !empty($install_positions) ? $install_positions->groupBy(['install_tier_unique_code']) : [],
                'installed_positions' => $installed_positions,
                'install_shelf_as_json' => $install_shelf ? $install_shelf->toJson() : collect([])->toJson(),
                'installed_entire_instances_as_json' => $installed_entire_instances->toJson(),
            ]);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Factory|View
     */
    final public function create()
    {
        $maintains = [];
        $workshops = Maintain::with(['Subs'])->where('parent_unique_code', env('ORGANIZATION_CODE'))->get();
        foreach ($workshops as $maintain) {
            $children = [];
            foreach ($maintain->Subs as $item) {
                $children[] = [
                    'value' => $item->unique_code,
                    'label' => $item->name,
                ];
            }
            $maintains[] = [
                'value' => $maintain->unique_code,
                'label' => $maintain->name,
                'children' => $children
            ];
        }
        $installRoomTypes = InstallRoom::$TYPES;
        $roomTypes = [];
        foreach ($installRoomTypes as $k => $v) {
            $roomTypes[] = [
                'label' => $v,
                'value' => $k,
            ];
        }

        return view('Install.Location.create', [
            'maintains' => TextHelper::toJson($maintains),
            'roomTypes' => TextHelper::toJson($roomTypes),
            'firstRoomTypes' => array_key_first($installRoomTypes)
        ]);
    }

    /**
     * 机房数据列表
     * @return false|JsonResponse|string
     */
    final public function roomWithIndex()
    {
        try {
            $rooms = [];
            DB::table("install_rooms as ir")
                ->selectRaw(implode(",", [
                    "ir.id as id",
                    "ir.unique_code as unique_code",
                    "ir.type as room_type",
                    "ir.station_unique_code as station_unique_code",
                    "s.name as station_name",
                    "sc.name as workshop_name",
                    "'' as type_name"
                ]))
                ->join(DB::raw("maintains s"), "s.unique_code", "=", "ir.station_unique_code")
                ->join(DB::raw("maintains sc"), "sc.unique_code", "=", "s.parent_unique_code")
                ->orderByDesc("sc.name")
                ->get()
                ->each(function ($datum) use (&$rooms) {
                    $rooms[] = [
                        "id" => $datum->id,
                        "unique_code" => $datum->unique_code,
                        "station_unique_code" => $datum->station_unique_code,
                        "station_name" => $datum->station_name,
                        "workshop_name" => $datum->workshop_name,
                        "type_name" => InstallRoom::$TYPES[$datum->room_type],
                    ];
                });

            $json = [
                "code" => 0,
                "msg" => "",
                "count" => count($rooms),
                "data" => $rooms
            ];

            return response()->json($json);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 机房新建
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function roomWithStore(Request $request): JsonResponse
    {
        try {
            $maintain_unique_code = $request->get('maintain_unique_code', '');
            $type = $request->get('type', '');
            if (empty($maintain_unique_code)) return JsonResponseFacade::errorValidate('请选择车间/车站');
            $stationUniqueCode = '';
            if (!empty($maintain_unique_code)) {
                $tmp = explode(',', $maintain_unique_code);
                $workshopUniqueCode = $tmp[0] ?? '';
                $stationUniqueCode = $tmp[1] ?? '';
            }
            $request['unique_code'] = $this->_installUniqueCode . $type . $this->_organizationCode . $stationUniqueCode;
            $request['station_unique_code'] = $stationUniqueCode;
            $v = ValidateHelper::firstErrorByRequest($request, new InstallRoomStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $installRoom = new InstallRoom();
            $req = $request->all();
            $req["name"] = InstallRoom::$TYPES[$type];
            $installRoom->fill($req)->saveOrFail();
            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 机房编辑
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function roomWithUpdate(Request $request, $id): JsonResponse
    {
        try {

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 机房删除
     * @param $id
     * @return JsonResponse
     */
    final public function roomWithDestroy($id): JsonResponse
    {
        try {
            $installRoom = InstallRoom::with(['WithInstallPlatoons'])->where('id', $id)->firstOrFail();
            if ($installRoom->WithInstallPlatoons->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除下级排');
            $installRoom->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 排列表
     * @param Request $request
     * @return false|string
     */
    final public function platoonWithIndex(Request $request)
    {
        try {
            $installRoomUniqueCode = $request->get('install_room_unique_code', '');
            $installPlatoons = DB::table('install_platoons')->select('id', 'name', 'unique_code', 'install_room_unique_code')->where('install_room_unique_code', $installRoomUniqueCode)->get();
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => $installPlatoons->count(),
                'data' => $installPlatoons->toArray()
            ];
            return TextHelper::toJson($json);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 排新建
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function platoonWithStore(Request $request): JsonResponse
    {
        try {
            $installRoomUniqueCode = $request->get('install_room_unique_code', '');
            if (empty($installRoomUniqueCode)) return JsonResponseFacade::errorValidate('请选择机房');

            // 重复验证
            $repeat_check = DB::table("install_platoons")
                ->where("name", $request->get("name"))
                ->where("install_room_unique_code", $request->get("install_room_unique_code"))
                ->exists();
            if ($repeat_check) return JsonResponseFacade::errorValidate("名称重复");

            $installPlatoon = new InstallPlatoon();
            $uniqueCode = $installPlatoon->getUniqueCode($installRoomUniqueCode);
            $request['unique_code'] = $uniqueCode;
            $v = ValidateHelper::firstErrorByRequest($request, new InstallPlatoonStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $installPlatoon->fill($request->all())->saveOrFail();
            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 排编辑
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function platoonWithUpdate(Request $request, $id): JsonResponse
    {
        try {
            $installPlatoon = InstallPlatoon::with([])->where('id', $id)->firstOrFail();
            $name = $request->get('name', '');

            // 重复验证
            $repeat_check = InstallPlatoon::with([])
                ->where("name", $request->get("name"))
                ->where("install_room_unique_code", $installPlatoon->install_room_unique_code)
                ->where("id", "<>", $installPlatoon->id)
                ->exists();
            if ($repeat_check) return JsonResponseFacade::errorValidate("名称重复");

            if (empty($name)) return JsonResponseFacade::errorValidate('名字不能为空');
            $installPlatoon->fill(['name' => $name])->saveOrFail();
            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 排删除
     * @param $id
     * @return JsonResponse
     */
    final public function platoonWithDestroy($id): JsonResponse
    {
        try {
            $installPlatoon = InstallPlatoon::with(['WithInstallShelves'])->where('id', $id)->firstOrFail();
            if ($installPlatoon->WithInstallShelves->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除下级柜架');
            $installPlatoon->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 柜架列表
     * @param Request $request
     * @return false|string
     */
    final public function shelfWithIndex(Request $request)
    {
        try {
            $installPlatoonUniqueCode = $request->get('install_platoon_unique_code', '');
            $installShelves = DB::table('install_shelves')->select('id', 'name', 'unique_code', 'install_platoon_unique_code')->where('install_platoon_unique_code', $installPlatoonUniqueCode)->get();
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => $installShelves->count(),
                'data' => $installShelves->toArray()
            ];
            return TextHelper::toJson($json);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 柜架新建
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function shelfWithStore(Request $request): JsonResponse
    {
        try {
            $installPlatoonUniqueCode = $request->get('install_platoon_unique_code', '');
            if (empty($installPlatoonUniqueCode)) return JsonResponseFacade::errorValidate('请选择排');
            $installShelf = new InstallShelf();
            $uniqueCode = $installShelf->getUniqueCode($installPlatoonUniqueCode);
            $request['unique_code'] = $uniqueCode;
            $v = ValidateHelper::firstErrorByRequest($request, new InstallShelfStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);

            $installShelf->fill($request->all())->saveOrFail();
            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 柜架编辑
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function shelfWithUpdate(Request $request, $id): JsonResponse
    {
        try {
            $installShelf = InstallShelf::with([])->where('id', $id)->firstOrFail();
            $name = $request->get('name', '');
            if (empty($name)) return JsonResponseFacade::errorValidate('名字不能为空');
            $installShelf->fill(['name' => $name])->saveOrFail();
            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 柜架删除
     * @param $id
     * @return JsonResponse
     */
    final public function shelfWithDestroy($id): JsonResponse
    {
        try {
            $installShelf = InstallShelf::with(['WithInstallTiers'])->where('id', $id)->firstOrFail();
            if ($installShelf->WithInstallTiers->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除下层');
            $installShelf->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层列表
     * @param Request $request
     * @return false|string
     */
    final public function tierWithIndex(Request $request)
    {
        try {
            $installShelfUniqueCode = $request->get('install_shelf_unique_code', '');
            $installTiers = DB::table('install_tiers')->select('id', 'name', 'unique_code', 'install_shelf_unique_code')->where('install_shelf_unique_code', $installShelfUniqueCode)->get();
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => $installTiers->count(),
                'data' => $installTiers->toArray()
            ];
            return TextHelper::toJson($json);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层新建
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function tierWithStore(Request $request): JsonResponse
    {
        try {
            $installShelfUniqueCode = $request->get('install_shelf_unique_code', '');
            if (empty($installShelfUniqueCode)) return JsonResponseFacade::errorValidate('请选择柜架');

            $last_install_tier = InstallTier::with([])->where('install_shelf_unique_code', $installShelfUniqueCode)->orderByDesc('name')->first();
            $last_install_tier_name = intval(@$last_install_tier->name ?: 0) + 1;

            $tier_count = $request->get('tier_count', 0) ?: 0;
            if ($tier_count <= 0) return JsonResponseFacade::errorValidate('层数量必须是大于0的整数');

            for ($i = $last_install_tier_name; $i < $last_install_tier_name + $tier_count; $i++) {
                InstallTier::with([])->create([
                    'name' => $i,
                    'unique_code' => InstallTier::generateUniqueCode($installShelfUniqueCode),
                    'install_shelf_unique_code' => $installShelfUniqueCode,
                ]);
            }

            // $installTier = new InstallTier();
            // $uniqueCode = $installTier->getUniqueCode($installShelfUniqueCode);
            // $request['unique_code'] = $uniqueCode;
            // $v = ValidateHelper::firstErrorByRequest($request, new InstallTierStoreRequest());
            // if ($v !== true) return JsonResponseFacade::errorValidate($v);
            //
            // $installTier->fill($request->all())->saveOrFail();
            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层编辑
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function tierWithUpdate(Request $request, $id): JsonResponse
    {
        try {
            $installTier = InstallTier::with([])->where('id', $id)->firstOrFail();
            $tier_name = $request->get('tier_name');
            if (!$tier_name && $tier_name !== '0') return JsonResponseFacade::errorValidate('名字不能为空');

            $installTier->fill(['name' => $tier_name])->saveOrFail();
            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层删除
     * @param $id
     * @return JsonResponse
     */
    final public function tierWithDestroy($id): JsonResponse
    {
        try {
            $installTier = InstallTier::with(['WithInstallPositions'])->where('id', $id)->firstOrFail();
            if ($installTier->WithInstallPositions->isNotEmpty()) return JsonResponseFacade::errorValidate('请删除下级位');
            $installTier->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 位列表
     * @param Request $request
     * @return false|string
     */
    final public function positionWithIndex(Request $request)
    {
        try {
            $installTierUniqueCode = $request->get('install_tier_unique_code', '');
            $installPositions = DB::table('install_positions')->select('id', 'unique_code', 'name', 'install_tier_unique_code')->where('install_tier_unique_code', $installTierUniqueCode)->get();
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => $installPositions->count(),
                'data' => $installPositions->toArray()
            ];
            return TextHelper::toJson($json);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 位新建
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function positionWithStore(Request $request): JsonResponse
    {
        try {
            $installTierUniqueCode = $request->get('install_tier_unique_code', '');
            if (empty($installTierUniqueCode)) return JsonResponseFacade::errorValidate('请选择层');

            // $position_name = $request->get('position_name');
            // InstallPosition::with([])
            //     ->create([
            //         'unique_code' => InstallPosition::generateUniqueCode($installTierUniqueCode),
            //         'install_tier_unique_code' => $installTierUniqueCode,
            //         'name' => $position_name,
            //     ]);

            // $empty_position_count = $request->get('empty_position_count', 0) ?? 0;
            // if ($empty_position_count > 0) {
            //     $installPosition = new InstallPosition();
            //     $lastUniqueCode = $installPosition->getLastUniqueCode($installTierUniqueCode);
            //     $name = 0;
            //     $insert = [];
            //     for ($i = 1; $i <= $empty_position_count; $i++) {
            //         $lastUniqueCode++;
            //         $name++;
            //         $uniqueCode = $installTierUniqueCode . sprintf("%02d", $lastUniqueCode);
            //         $insert[] = [
            //             'created_at' => $this->_currentTime,
            //             'updated_at' => $this->_currentTime,
            //             'unique_code' => $uniqueCode,
            //             'install_tier_unique_code' => $installTierUniqueCode,
            //             'name' => "0-{$name}",
            //         ];
            //     }
            // }
            // DB::table('install_positions')->insert($insert);

            $positionCount = $request->get('position_count', 1);
            $installPosition = new InstallPosition();
            $lastUniqueCode = $installPosition->getLastUniqueCode($installTierUniqueCode);
            $insert = [];
            if ($positionCount > 0) {
                for ($i = 1; $i <= $positionCount; $i++) {
                    $lastUniqueCode += 1;
                    $uniqueCode = $installTierUniqueCode . sprintf("%02d", $lastUniqueCode);
                    $insert[] = [
                        'created_at' => $this->_currentTime,
                        'updated_at' => $this->_currentTime,
                        'unique_code' => $uniqueCode,
                        'install_tier_unique_code' => $installTierUniqueCode,
                        'name' => $lastUniqueCode,
                    ];
                }
                DB::table('install_positions')->insert($insert);
            }
            return JsonResponseFacade::created();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 位编辑
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws \Throwable
     */
    final public function positionWithUpdate(Request $request, $id): JsonResponse
    {
        try {
            $installPosition = InstallPosition::with([])->where('id', $id)->firstOrFail();
            $v = ValidateHelper::firstErrorByRequest($request, new InstallPositionUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $installPosition->fill($request->all())->saveOrFail();
            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 位删除
     * @param $id
     * @return JsonResponse
     */
    final public function positionWithDestroy($id): JsonResponse
    {
        try {
            $installPosition = InstallPosition::with([])->where('id', $id)->firstOrFail();
            $installPosition->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取排with车站
     * @param Request $request
     * @return mixed
     */
    final public function getPlatoonWithStation(Request $request)
    {
        try {
            $station_unique_code = $request->get('station_unique_code', '');
            $platoons = DB::table('install_platoons as ip')
                ->select('ip.name', 'ip.unique_code')
                ->join(DB::raw('install_rooms ir'), 'ip.install_room_unique_code', '=', 'ir.unique_code')
                ->when(
                    !empty($station_unique_code),
                    function ($query) use ($station_unique_code) {
                        return $query->where('ir.station_unique_code', $station_unique_code);
                    }
                )
                ->get();

            return JsonResponseFacade::data($platoons->isNotEmpty() ? $platoons->toArray() : []);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 根据车站获取房间列表
     * @return mixed
     */
    final public function getInstallRooms()
    {
        try {
            if (request('station_name')) {
                $station = DB::table('maintains as m')->where('m.name', request('station_name'))->first();
                if (!$station) return JsonResponseFacade::data(['rooms' => []]);
                $install_rooms = ModelBuilderFacade::init(
                    request(),
                    InstallRoom::with([]),
                    ['station_name']
                )
                    ->extension(function ($install_room) use ($station) {
                        return $install_room->where('station_unique_code', $station->unique_code);
                    })
                    ->all();
            } elseif (request('station_unique_code')) {

                $install_rooms = ModelBuilderFacade::init(
                    request(),
                    InstallRoom::with([])
                )
                    ->all();
            } else {
                return JsonResponseFacade::errorForbidden('车站参数错误');
            }

            return JsonResponseFacade::data(['install_rooms' => $install_rooms]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 根据房间获取排列表
     * @return mixed
     */
    final public function getInstallPlatoons()
    {
        try {
            $install_platoons = ModelBuilderFacade::init(request(), InstallPlatoon::with([]))->all();

            return JsonResponseFacade::data(['install_platoons' => $install_platoons]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 根据排获取柜
     * @return mixed
     */
    final public function getInstallShelves()
    {
        try {
            $install_shelves = ModelBuilderFacade::init(
                request(),
                InstallShelf::with(["WithInstallPlatoon"]),
                ["install_room_unique_code"]
            )
                ->extension(function ($builder) {
                    $builder->when(request("install_room_unique_code"), function ($query) {
                        $query->whereHas("WithInstallPlatoon", function ($WithInstallPlatoon) {
                            $WithInstallPlatoon->where("install_room_unique_code", request("install_room_unique_code"));
                        });
                    });
                })
                ->all();

            return JsonResponseFacade::data(['install_shelves' => $install_shelves]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 根据柜获取层
     * @return mixed
     */
    final public function getInstallTiers()
    {
        try {
            $install_tiers = ModelBuilderFacade::init(request(), InstallTier::with([]))->all();

            return JsonResponseFacade::data(['install_tiers' => $install_tiers]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 根据层获取位
     * @return mixed
     */
    final public function getInstallPositions()
    {
        try {
            $install_positions = ModelBuilderFacade::init(request(), InstallPosition::with([]))->all();

            return JsonResponseFacade::data(['install_positions' => $install_positions]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * drawing scene install location front view
     * usage
     * GET params：
     *     1. bg_class background class name by adminlte
     *     2. install_position_unique_code current highlight location unique code
     * @param string $install_shelf_unique_code install shelf unique code
     * @return Factory|Application|View
     */
    final public function getCanvas(string $install_shelf_unique_code)
    {
        $install_shelf = InstallShelf::with([
            'WithInstallTiers',
            'WithInstallTiers.WithInstallPositions',
        ])
            ->where('unique_code', $install_shelf_unique_code)
            ->first();

        $installed_entire_instances = EntireInstance::with([])
            ->select(['model_name', 'model_unique_code', 'maintain_location_code', 'identity_code',])
            ->where('maintain_location_code', 'like', $install_shelf->unique_code . '%')
            ->get()
            ->groupBy('maintain_location_code');

        return view('Install.Location.canvas', [
            'install_shelf_as_json' => $install_shelf->toJson(),
            'installed_entire_instances_as_json' => $installed_entire_instances->toJson(),
        ]);
    }

    /**
     * drawing scene install location front view in station
     */
    final public function getCanvases(string $station_unique_code)
    {
        $station = Maintain::with([])->where('unique_code', $station_unique_code)->first();
        if (!$station) return back()->with('danger', '车站不存在');

        $install_shelves = InstallShelf::with([
            'WithInstallPlatoon',
            'WithInstallPlatoon.WithInstallRoom',
            'WithInstallTiers' => function ($WithInstallTiers) {
                $WithInstallTiers->orderByDesc('id');
            },
            'WithInstallTiers.WithInstallPositions',
        ])
            ->whereHas('WithInstallPlatoon.WithInstallRoom', function (Builder $WithInstallRoom) use ($station_unique_code) {
                $WithInstallRoom->where('station_unique_code', $station_unique_code);
            })
            ->get();

        return view('Install.Location.canvases', [
            'station' => $station,
            'install_shelves' => $install_shelves,
        ]);
    }

    /**
     * 下载批量上传模板
     */
    final public function getUpload()
    {
        try {
            ExcelWriteHelper::download(
                function ($excel) {
                    $excel->setActiveSheetIndex(0);
                    $current_sheet = $excel->getActiveSheet();

                    // 首行数据
                    $first_row_data = [
                        ['context' => '车站（必须提前手动车站）', 'color' => 'red', 'width' => 30], // A
                        ['context' => '机房（必须提前手动创建机房）', 'color' => 'red', 'width' => 30],  // B
                        ['context' => '排', 'color' => 'black', 'width' => 20],  // C
                        ['context' => '架', 'color' => 'black', 'width' => 20],  // D
                        ['context' => '层', 'color' => 'black', 'width' => 20],  // E
                        ['context' => '空位', 'color' => 'black', 'width' => 20], // F
                        ['context' => '位（总数）', 'color' => 'black', 'width' => 25],  // G
                    ];
                    // 填充首行数据
                    foreach ($first_row_data as $col => $first_row_datum) {
                        $col_for_excel = ExcelWriteHelper::int2Excel($col);
                        ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                        $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                        $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                        $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                    }

                    return $excel;
                },
                "批量创建上道位置",
                ExcelWriteHelper::$VERSION_5
            );
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量上传
     * @param Request $request
     * @return RedirectResponse
     */
    final public function postUpload(Request $request)
    {
        try {
            $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                ->originRow(2)
                ->withSheetIndex(0);

            if (empty(@$excel['success'])) return back()->with('danger', 'excel 内容为空');

            $total = 0;

            DB::beginTransaction();
            foreach ($excel['success'] as $row_data) {
                if (empty(array_filter($row_data, function ($val) {
                    return !empty($val);
                }))) continue;

                list (
                    $station_name,
                    $install_room_name,
                    $install_platoon_name,
                    $install_shelf_name,
                    $install_tier_name,
                    $install_position_empty_number,
                    $install_position_number
                    ) = $row_data;
                $station = Maintain::with([])->where('name', $station_name)->where('type', 'STATION')->first();

                if (!$station) return back()->with('danger', "车站：{$station_name}不存在，必须先手动创建车站");

                $install_room_type = array_flip(InstallRoom::$TYPES)[$install_room_name] ?? '';
                if (!$install_room_type) return back()->with('danger', "机房：{$install_room_name}($station_name)不存在，必须先手动创建机房");
                $install_room = InstallRoom::with([])->where('station_unique_code', $station->unique_code)->where('type', $install_room_type)->first();
                if (!$install_room) return back()->with('danger', "机房：{$install_room_name}($station_name)不存在，必须先手动创建机房");

                $install_platoon = InstallPlatoon::with([])->where('install_room_unique_code', $install_room->unique_code)->where('name', $install_platoon_name)->first();
                if (!$install_platoon) {
                    $install_platoon = InstallPlatoon::with([])
                        ->create([
                            'name' => $install_platoon_name,
                            'unique_code' => InstallPlatoon::generateUniqueCode($install_room->unique_code),
                            'install_room_unique_code' => $install_room->unique_code,
                        ]);
                }

                $install_shelf = InstallShelf::with([])->where('install_platoon_unique_code', $install_platoon->unique_code)->where('name', $install_shelf_name)->first();
                if (!$install_shelf) {
                    $install_shelf = InstallShelf::with([])
                        ->create([
                            'name' => $install_shelf_name,
                            'unique_code' => InstallShelf::generateUniqueCode($install_platoon->unique_code),
                            'install_platoon_unique_code' => $install_platoon->unique_code,
                        ]);
                }

                $install_tier = InstallTier::with([])->where('install_shelf_unique_code', $install_shelf->unique_code)->where('name', $install_tier_name)->first();
                if (!$install_tier) {
                    $install_tier = InstallTier::with([])
                        ->create([
                            'name' => $install_tier_name,
                            'unique_code' => InstallTier::generateUniqueCode($install_shelf->unique_code),
                            'install_shelf_unique_code' => $install_shelf->unique_code,
                        ]);
                }

                // 创建空位
                if ($install_position_empty_number) {
                    if (!is_numeric($install_position_empty_number)) return back()->with('danger', "空位必须填写数字");
                    for ($i = 0; $i < intval($install_position_empty_number); $i++) {
                        $position_empty = InstallPosition::with([])
                            ->create([
                                'name' => '0' . ($i + 1),
                                'unique_code' => InstallPosition::generateUniqueCode($install_tier->unique_code),
                                'install_tier_unique_code' => $install_tier->unique_code,
                                'volume' => 2,
                            ]);
                    }
                }

                // 创建上道位
                if ($install_position_number) {
                    if (!is_numeric($install_position_number)) return back()->with('danger', "上道位置必须填写数字");
                    for ($i = 0; $i < intval($install_position_number); $i++) {
                        $position = InstallPosition::with([])
                            ->create([
                                'name' => $i + 1,
                                'unique_code' => InstallPosition::generateUniqueCode($install_tier->unique_code),
                                'install_tier_unique_code' => $install_tier->unique_code,
                                'volume' => 1,
                            ]);
                        $total++;
                    }
                }
            }
            DB::commit();

            return back()->with('success', "成功上传：{$total}个位置");
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 同步到电子车间
     * @param Request $request
     * @throws Exception
     */
    final public function PostSyncToElectricWorkshop(Request $request)
    {
        $date = $request->get("sync_date");

        try {
            $date = Carbon::parse($date);
        } catch (Exception $e) {
            throw new ForbiddenException("日期格式不正常：请使用<年年年年-月月-日日>格式");
        }

        SyncFacade::InstallLocationToElectricWorkshop($date, $date);

        return JsonResponseFacade::ok("同步成功");
    }
}
