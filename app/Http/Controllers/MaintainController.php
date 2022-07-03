<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\OrganizationFacade;
use App\Facades\ResponseFacade;
use App\Model\Maintain;
use App\Model\StationElectricImage;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Jericho\BadRequestException;
use Jericho\CurlHelper;
use Jericho\Excel\ExcelReadHelper;
use Jericho\HttpResponseHelper;
use Jericho\Model\Log;

class MaintainController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic';
    private $_root_url = null;
    private $_auth = null;

    public function __construct()
    {
        $this->_spas_protocol = env('SPAS_PROTOCOL');
        $this->_spas_url_root = env('SPAS_URL_ROOT');
        $this->_spas_port = env('SPAS_PORT');
        $this->_spas_api_root = env('SPAS_API_ROOT');
        $this->_spas_username = env('SPAS_USERNAME');
        $this->_spas_password = env('SPAS_PASSWORD');
        $this->_root_url = "{$this->_spas_protocol}:// {$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = ["Username:{$this->_spas_username}", "Password:{$this->_spas_password}"];
    }

    /**
     * Display a listing of the resource.
     * @return Factory|Application|View
     */
    public function index()
    {
        $maintains = (new Maintain())
            ->ReadMany()
            ->with(["Parent"])
            ->when(
                request("type"),
                function ($type, $query) {
                }, function ($query) {
                $query->whereIn("type", ["SCENE_WORKSHOP", "STATION",]);
            })
            ->where("is_show", true)
            ->when(
                request('name'),
                function ($query, $name) {
                    $query->where('name', 'like', "%{$name}%");
                }
            );

        $sql = Log::sqlLanguage(function () {
            (new Maintain())
                ->ReadMany()
                ->with(["Parent"])
                ->when(
                    request("type"),
                    function ($type, $query) {
                    }, function ($query) {
                    $query->whereIn("type", ["SCENE_WORKSHOP", "STATION",]);
                })
                ->where("is_show", true)
                ->when(
                    request('name'),
                    function ($query, $name) {
                        $query->where('name', 'like', "%{$name}%");
                    }
                )
                ->get();
        });

        if (request()->ajax()) {
            return JsonResponseFacade::dict(["maintains" => $maintains->get(), "maintain_types" => $maintains->pluck("type")->unique()->values(), "sql" => $sql,]);
        } else {
            return view("Maintain.index", ["maintains" => $maintains->paginate(100),]);
        }
    }

    /**
     * 报表页面
     * @return Factory|View
     */
    public function report()
    {
        $maintains = Maintain::with([
            'EntireInstances' => function ($query) {
                $query->where('status', '<>', 'SCRAP')
                    ->orderBy('status');
            }
        ])
            ->orderByDesc('id')
            ->paginate();

        return view('Maintain.report', [
            'maintains' => $maintains,
        ]);
    }

    /**`
     * Show the form for creating a new resource.
     *
     * @return Factory|Application|View
     */
    public function create()
    {
        $lines = DB::table('lines')->get()->toArray();
        $workShops = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->get()->toArray();

        $workshopUniqueCode = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->where('parent_unique_code', env('ORGANIZATION_CODE'))->orderByDesc('unique_code')->value('unique_code');
        if ($workshopUniqueCode) {
            $workshopUniqueCode_2 = str_pad(substr($workshopUniqueCode, -2) + 1, 2, 0, STR_PAD_LEFT);
            $workshopUniqueCode = env('ORGANIZATION_CODE') . 'C' . $workshopUniqueCode_2;
        } else {
            $workshopUniqueCode = env('ORGANIZATION_CODE') . 'C01';
        }

        $stationUniqueCode = DB::table('maintains')->where('type', 'STATION')->where('parent_unique_code', 'like', env('ORGANIZATION_CODE') . '%')->orderByDesc('unique_code')->value('unique_code');
        if ($stationUniqueCode) {
            $stationUniqueCode_5 = str_pad(substr($stationUniqueCode, -5) + 1, 5, 0, STR_PAD_LEFT);
            $stationUniqueCode = 'G' . $stationUniqueCode_5;
        } else {
            $stationUniqueCode = 'G' . substr(env('ORGANIZATION_CODE'), -2, 2) . '001';
        }
        return view('Maintain.create', [
            'lines' => $lines,
            'workshopUniqueCode' => $workshopUniqueCode,
            'stationUniqueCode' => $stationUniqueCode,
            'workShops' => $workShops
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $name = $request->get('name');
            $unique_code = $request->get('unique_code');
            $line = $request->get('line');
            $lon = $request->get('lon');
            $lat = $request->get('lat');
            $contact = $request->get('contact');
            $contact_phone = $request->get('contact_phone');
            $contact_address = $request->get('contact_address');
            $is_show = $request->get('is_show');
            $type = $request->get('type');
            $created_at = date('Y-m-d H:i:s');

            if (empty($name)) return Response::make('请填写名称', 422);
            // if (empty($unique_code)) return Response::make('请填写统一代码', 422);
            if (DB::table('maintains')->where('name', $name)->exists()) return Response::make('名称重复', 422);
            if (DB::table('maintains')->where('unique_code', $unique_code)->exists()) return Response::make('编码重复', 422);

            if ($type == 'workshop') {
                // 车间
                $maintainId = DB::table('maintains')->insertGetId([
                    'created_at' => $created_at,
                    'unique_code' => $unique_code,
                    'name' => $name,
                    'parent_unique_code' => env('ORGANIZATION_CODE'),
                    'type' => 'SCENE_WORKSHOP',
                    'lon' => $lon,
                    'lat' => $lat,
                    'contact' => $contact,
                    'contact_phone' => $contact_phone,
                    'contact_address' => $contact_address,
                    'is_show' => $is_show,
                ]);
                // 车间线别关联关系
                // foreach ($line as $value) {
                // DB::table('lines_maintains')->insert([
                // 'lines_id' => $value,
                // 'maintains_id' => $maintainId,
                // ]);
                // }

                // 计算距离
                if ($lon && $lat) {
                    $maintains = DB::table('maintains')->where('lon', '<>', '')->where('lat', '<>', '')->get()->toArray();
                    foreach ($maintains as $maintain) {
                        $radLat1 = deg2rad($lat); // 纬度
                        $radLng1 = deg2rad($lon); // 经度
                        $radLat2 = deg2rad($maintain->lat); // 纬度
                        $radLng2 = deg2rad($maintain->lon); // 经度
                        $a = $radLat1 - $radLat2;
                        $b = $radLng1 - $radLng2;
                        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
                        DB::table('distance')->insert([
                            'maintains_id' => $maintainId,
                            'maintains_name' => $maintain->name,
                            'distance' => $s
                        ]);
                    }
                }
            } else {
                // 车站
                $workshop = $request->get('workshop');
                // if (empty($line)) return Response::make('请选择线别', 422);
                if (empty($workshop)) return Response::make('请选择车间', 422);
                $parent_unique_code = DB::table('maintains')->where('id', $workshop)->value('unique_code');
                $stationId = DB::table('maintains')->insertGetId([
                    'created_at' => $created_at,
                    'unique_code' => $unique_code,
                    'name' => $name,
                    'parent_unique_code' => $parent_unique_code,
                    'type' => 'STATION',
                    'lon' => $lon,
                    'lat' => $lat,
                    'contact' => $contact,
                    'contact_phone' => $contact_phone,
                    'contact_address' => $contact_address,
                    'is_show' => $is_show
                ]);
                // 车站线别关联关系
                if ($line) {
                    foreach ($line as $value) {
                        DB::table('lines_maintains')->insert([
                            'lines_id' => $value,
                            'maintains_id' => $stationId
                        ]);
                    }
                }

                // 计算距离
                if ($lon && $lat) {
                    $maintains = DB::table('maintains')->where('lon', '<>', '')->where('lat', '<>', '')->get()->toArray();
                    foreach ($maintains as $maintain) {
                        $radLat1 = deg2rad($lat); // 纬度
                        $radLng1 = deg2rad($lon); // 经度
                        $radLat2 = deg2rad($maintain->lat); // 纬度
                        $radLng2 = deg2rad($maintain->lon); // 经度
                        $a = $radLat1 - $radLat2;
                        $b = $radLng1 - $radLng2;
                        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
                        DB::table('distance')->insert([
                            'maintains_id' => $stationId,
                            'maintains_name' => $maintain->name,
                            'distance' => $s
                        ]);
                    }
                }
            }
            return Response::make('新建成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误' . $exceptionMessage, 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     * @param Request $request
     * @param $uniqueCode
     * @return Factory|\Illuminate\Http\RedirectResponse|View
     */
    public function edit(Request $request, $uniqueCode)
    {
        try {
            $type = $request->get('type');
            $page = $request->get('page');

            if ($type == 'STATION') {
                $lines = DB::table('lines')->get()->toArray();
                $workShops = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->get()->toArray();
                $id = DB::table('maintains')->where('unique_code', $uniqueCode)->value('id');
                $linesMaintains = DB::table('lines_maintains')->where('maintains_id', $id)->select(['lines_id'])->get(['lines_id'])->toArray();
                $maintainsIds = [];
                foreach ($linesMaintains as $v)
                    @$maintainsIds[] = $v->lines_id;
            } else {
                $lines = [];
                $workShops = [];
                @$maintainsIds = [];
            }
            $maintain = DB::table('maintains')->where('unique_code', $uniqueCode)->first();
            $station_electric_images = StationElectricImage::with([])->where('maintain_station_unique_code', $uniqueCode)->orderBy('sort')->orderBy('id')->get();

            return view('Maintain.edit')
                ->with('type', $type)
                ->with('lines', $lines)
                ->with('workShops', $workShops)
                ->with('maintain', $maintain)
                ->with('maintainsIds', @$maintainsIds)
                ->with('page', $page)
                ->with('current_unique_code', $uniqueCode)
                ->with('station_electric_images', $station_electric_images);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uniqueCode
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function update(Request $request, $uniqueCode)
    {
        try {
            if (!$request->get('name', '') ?? '') return JsonResponseFacade::errorValidate('请填写名称');
            $maintain = Maintain::with([])->where("unique_code", $uniqueCode)->first();
            if (!$maintain) return JsonResponseFacade::errorEmpty("车间或车站没有找到");

            // 旧名称
            $old_name = $maintain->name;
            // 修改归属关系和对应数据
            switch ($maintain->prototype("type")) {
                case 'STATION':
                    // 编辑相关归属数据
                    OrganizationFacade::UpdateStationName($old_name, $request->get("name"));

                    // 删除原有线别与车站对应关系
                    DB::table('lines_maintains')->where('maintains_id', $maintain->id)->delete();

                    // 重建线别与车站对应关系
                    $lines_maintains_inserts = [];
                    foreach ($request->get('line') as $line_id) {
                        $lines_maintains_inserts[] = [
                            'lines_id' => $line_id,
                            'maintains_id' => $maintain->id,
                        ];
                    }
                    DB::table('lines_maintains')->insert($lines_maintains_inserts);
                    break;
                case 'SCENE_WORKSHOP':
                case 'WORKSHOP':
                    // 编辑相关归属数据
                    OrganizationFacade::UpdateWorkshopName($old_name, $request->get("name"));
                    break;
            }
            // 修改车间、车站数据
            $maintain
                ->fill([
                    "updated_at" => now(),
                    "name" => $request->get("name", "") ?? "",
                    "lon" => $request->get("lon", "") ?? "",
                    "lat" => $request->get("lat", "") ?? "",
                    "contact" => $request->get("contact", "") ?? "",
                    "contact_phone" => $request->get("contact_phone", "") ?? "",
                    "contact_address" => $request->get("contact_address", "") ?? "",
                    "is_show" => $request->get("is_show", "") ?? "",
                ])
                ->saveOrFail();

            return JsonResponseFacade::updated([], '编辑成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $uniqueCode
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::table('maintains')->where('id', $id)->delete();
            return Response::make('删除成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 距离计算
     * @return \Illuminate\Http\Response
     */
    public function distance()
    {
        // 计算全部车间车站距离
        DB::table('distance')->delete();
        $maintains = DB::table('maintains')->where('lon', '<>', '')->where('lat', '<>', '')->get()->toArray();
        foreach ($maintains as $maintain) {
            $radLat1 = deg2rad($maintain->lat); // 纬度
            $radLng1 = deg2rad($maintain->lon); // 经度
            foreach ($maintains as $value) {
                $radLat2 = deg2rad($value->lat); // 纬度
                $radLng2 = deg2rad($value->lon); // 经度
                $a = $radLat1 - $radLat2;
                $b = $radLng1 - $radLng2;
                $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
                DB::table('distance')->insert([
                    'maintains_id' => $maintain->id,
                    'maintains_name' => $value->name,
                    'distance' => $s
                ]);
            }
        }
        return Response::make('计算成功', 200);
    }

    /**
     * 从数据中台备份到本地
     */
    final public function getBackupFromSPAS()
    {
        try {
            # 同步供应商
            $scene_workshops_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/sceneWorkshopByParagraphUniqueCode/" . env('ORGANIZATION_CODE'),
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($scene_workshops_response['code'] != 200) return response()->json($scene_workshops_response['body'], $scene_workshops_response['code']);

            # 同步站场
            $stations_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/stationByParagraphUniqueCode/" . env('ORGANIZATION_CODE'),
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($stations_response['code'] != 200) return response()->json($stations_response['body'], $stations_response['code']);

            $current_time = date('Y-m-d H:i:s');

            # 写入供应商
            $insert_scene_workshops = [];
            foreach ($scene_workshops_response['body']['data'] as $datum) {
                $insert_scene_workshops[] = [
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'parent_unique_code' => $datum['paragraph'],
                    'type' => 'SCENE_WORKSHOP',
                ];
            }
            if ($insert_scene_workshops) {
                DB::table('maintains')->truncate();
                DB::table('maintains')->insert($insert_scene_workshops);
            }

            # 写入站场
            $insert_stations = [];
            foreach ($stations_response['body']['data'] as $datum) {
                $insert_stations[] = [
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'parent_unique_code' => $datum['scene_workshop'],
                    'type' => 'STATION'
                ];
            }

            if ($insert_stations) DB::table('maintains')->insert($insert_stations);

            return response()->json(['message' => '同步成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 上传线别/车间/车站Excel
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function UploadExcel(Request $request)
    {
        try {
            // NEW
            // if (!$request->hasFile('upLoadExcel')) return Response::make('上传文件失败', 302);
            // if (!in_array($request->file('upLoadExcel')->getClientMimeType(), [
            // 'application/vnd.ms-excel',
            // 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // 'application/octet-stream'
            // ])) return Response::make('只能上传excel', 302);
            //
            // $originRow = 2;
            // $excel = ExcelReadHelper::FROM_REQUEST($request, 'upLoadExcel')->originRow($originRow)->withSheetIndex(0);
            //
            // $created_at = date('Y-m-d H:i:s');
            //
            // DB::transaction(function () use ($request, $created_at, $excel) {
            //
            // DB::table('lines')->truncate();
            // DB::table('workshops')->truncate();
            // DB::table('stations')->truncate();
            // DB::table('lines_stations')->truncate();
            // foreach ($excel['success'] as $row) {
            // if ($row[22] == 'SCENE_WORKSHOP') {
            // // 线别导入
            // $lineName = $row[0];
            // $lineUniqueCode = $row[21];
            // if (!DB::table('lines')->where('name', $lineName)->exists()) {
            // DB::table('lines')->insert([
            // 'created_at' => $created_at,
            // 'name' => $lineName,
            // 'unique_code' => $lineUniqueCode
            // ]);
            // }
            //
            // // 现场车间导入
            // $workshopName = $row[2];
            // $workshopUniqueCode = $row[5];
            // $workshopLon = $row[15];
            // $workshopLat = $row[16];
            // $workshopContact = $row[18];
            // $workshopContactPhone = $row[17];
            // if (!DB::table('workshops')->where('name', $workshopName)->exists()) {
            // DB::table('workshops')->insert([
            // 'created_at' => $created_at,
            // 'unique_code' => $workshopUniqueCode,
            // 'name' => $workshopName,
            // 'lon' => $workshopLon,
            // 'lat' => $workshopLat,
            // 'contact' => $workshopContact,
            // 'contact_phone' => $workshopContactPhone,
            // 'type' => 'SCENE_WORKSHOP',
            // 'is_show' => '1'
            // ]);
            // }
            //
            // // 车站导入
            // $stationUniqueCode = $row[10];
            // $stationName = $row[7];
            // $stationLon = $row[11];
            // $stationLat = $row[12];
            // $stationContact = $row[13];
            // $stationContactPhone = $row[14];
            // if (!DB::table('stations')->where('name', $stationName)->exists()) {
            // DB::table('stations')->insert([
            // 'created_at' => $created_at,
            // 'workshop_unique_code' => $workshopUniqueCode,
            // 'unique_code' => $stationUniqueCode,
            // 'name' => $stationName,
            // 'lon' => $stationLon,
            // 'lat' => $stationLat,
            // 'contact' => $stationContact,
            // 'contact_phone' => $stationContactPhone,
            // 'is_show' => '1'
            // ]);
            // DB::table('lines_stations')->insert([
            // 'line_unique_code' => $lineUniqueCode,
            // 'station_code' => $stationUniqueCode
            // ]);
            // }
            // }else {
            // // 车间导入
            // $workshopName = $row[2];
            // $workshopUniqueCode = $row[5];
            // $workshopLon = $row[15];
            // $workshopLat = $row[16];
            // $workshopContact = $row[18];
            // $workshopContactPhone = $row[17];
            // if (!DB::table('workshops')->where('name', $workshopName)->exists()) {
            // DB::table('workshops')->insert([
            // 'created_at' => $created_at,
            // 'unique_code' => $workshopUniqueCode,
            // 'name' => $workshopName,
            // 'lon' => $workshopLon,
            // 'lat' => $workshopLat,
            // 'contact' => $workshopContact,
            // 'contact_phone' => $workshopContactPhone,
            // 'type' => 'WORKSHOP',
            // 'is_show' => '1'
            // ]);
            // }
            // }
            //
            // // breakdown_logs
            // DB::table('breakdown_logs')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // DB::table('breakdown_logs')->where('scene_workshop_name', $row[1])->update(['maintain_station_name' => $row[2]]);
            // // collect_equipment_order_entire_instances
            // DB::table('collect_device_order_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // DB::table('collect_device_order_entire_instances')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
            // DB::table('collect_device_order_entire_instances')->where('maintain_workshop_name', $row[1])->update(['maintain_workshop_name' => $row[2]]);
            // DB::table('collect_device_order_entire_instances')->where('maintain_workshop_unique_code', $row[3])->update(['maintain_workshop_unique_code' => $row[5]]);
            // // // entire_instances
            // // DB::table('entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // // DB::table('entire_instances')->where('maintain_workshop_name', $row[1])->update(['maintain_workshop_name' => $row[2]]);
            // // fix_workflows
            // DB::table('fix_workflows')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // // // repair_base_breakdown_order_entire_instances
            // DB::table('repair_base_breakdown_order_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // DB::table('repair_base_breakdown_order_entire_instances')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
            // // // station_locations
            // DB::table('station_locations')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // DB::table('station_locations')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
            // DB::table('station_locations')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
            // DB::table('station_locations')->where('scene_workshop_unique_code', $row[3])->update(['scene_workshop_unique_code' => $row[5]]);
            // // // temp_station_eis
            // DB::table('temp_station_eis')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // // // temp_station_position
            // DB::table('temp_station_position')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // DB::table('temp_station_position')->where('maintain_station_unique_code', $row[8])->update(['maintain_station_unique_code' => $row[10]]);
            // DB::table('temp_station_position')->where('scene_workshop_name', $row[1])->update(['scene_workshop_name' => $row[2]]);
            // DB::table('temp_station_position')->where('scene_workshop_unique_code', $row[3])->update(['scene_workshop_unique_code' => $row[5]]);
            // // // warehouse_in_batch_reports
            // DB::table('warehouse_in_batch_reports')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // // // warehouse_report_entire_instances
            // DB::table('warehouse_report_entire_instances')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // // // warehouse_storage_batch_reports
            // DB::table('warehouse_storage_batch_reports')->where('maintain_station_name', $row[6])->update(['maintain_station_name' => $row[7]]);
            // }
            // });
            //
            // return Response::make('导入成功', 200);


            // OLD
            if (!$request->hasFile('upLoadExcel')) return Response::make('上传文件失败', 302);
            if (!in_array($request->file('upLoadExcel')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return Response::make('只能上传excel', 302);

            $created_at = date('Y-m-d H:i:s');
            DB::beginTransaction();
            DB::table('lines')->truncate();
            DB::table('maintains')->truncate();
            DB::table('lines_maintains')->truncate();
            $originRow = 2;
            $excel = ExcelReadHelper::FROM_REQUEST($request, 'upLoadExcel')->originRow($originRow)->withSheetIndex(0);
            $i = 2;
            foreach ($excel['success'] as $row) {
                if (empty(array_filter($row, function ($value) {
                    return !empty($value) && !is_null($value);
                }))) continue;
                // 线别导入
                if (!DB::table('lines')->where('name', $row[0])->exists()) {
                    DB::table('lines')->insert([
                        'created_at' => $created_at,
                        'name' => $row[0],
                        'unique_code' => $row[21]
                    ]);
                    $i++;
                }
            }
            // 车间导入
            foreach ($excel['success'] as $row) {
                if (empty(array_filter($row, function ($value) {
                    return !empty($value) && !is_null($value);
                }))) continue;
                if (!DB::table('maintains')->where('name', $row[2])->exists()) {
                    DB::table('maintains')->insert([
                        'created_at' => $created_at,
                        'unique_code' => $row[5],
                        'name' => $row[2],
                        'parent_unique_code' => env('ORGANIZATION_CODE'),
                        'type' => 'SCENE_WORKSHOP',
                        'lon' => $row[15],
                        'lat' => $row[16],
                        'contact' => $row[18],
                        'contact_phone' => $row[17],
                        'is_show' => 1,
                    ]);
                } else {
                    DB::table('maintains')->where('name', $row[2])->update([
                        'updated_at' => $created_at,
                        'lon' => $row[15],
                        'lat' => $row[16],
                        'contact' => $row[18],
                        'contact_phone' => $row[17],
                        'is_show' => 1,
                    ]);
                }
            }
            // 车站导入
            foreach ($excel['success'] as $row) {
                if (empty(array_filter($row, function ($value) {
                    return !empty($value) && !is_null($value);
                }))) continue;
                if (!DB::table('maintains')->where('name', $row[7])->exists()) {
                    $stationId = DB::table('maintains')->insertGetId([
                        'created_at' => $created_at,
                        'unique_code' => $row[10],
                        'name' => $row[7],
                        'parent_unique_code' => $row[5],
                        'type' => 'STATION',
                        'lon' => $row[11],
                        'lat' => $row[12],
                        'contact' => $row[13],
                        'contact_phone' => $row[14],
                        'contact_address' => null,
                        'is_show' => 1
                    ]);
                    $lineId = DB::table('lines')->where('name', $row[0])->value('id');
                    DB::table('lines_maintains')->insert([
                        'lines_id' => $lineId,
                        'maintains_id' => $stationId
                    ]);
                } else {
                    $stationId = DB::table('maintains')->where('name', $row[7])->update([
                        'updated_at' => $created_at,
                        'parent_unique_code' => $row[5],
                        'lon' => $row[11],
                        'lat' => $row[12],
                        'contact' => $row[13],
                        'contact_phone' => $row[14],
                        'contact_address' => null,
                        'is_show' => 1
                    ]);
                    DB::table('lines_maintains')->where('maintains_id', $stationId)->delete();
                    $lineId = DB::table('lines')->where('name', $row[0])->value('id');
                    DB::table('lines_maintains')->insert([
                        'lines_id' => $lineId,
                        'maintains_id' => $stationId
                    ]);
                }
            }
            DB::commit();

            return ResponseFacade::created('导入成功')->json();
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return JsonResponseFacade::errorException($exception);
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 获取车站
     * @return JsonResponse
     */
    public function getStation()
    {
        try {
            $stations = Maintain::with([])
                ->select('id', 'unique_code', 'name', 'parent_unique_code', 'type')
                ->where('type', 'STATION')
                ->when(
                    request('scene_workshop_unique_code'),
                    function ($query) {
                        $query->where('parent_unique_code', request('scene_workshop_unique_code'));
                    }
                )
                ->when(
                    request('line_unique_code'),
                    function ($query) {
                        $station_ids = DB::table('lines_maintains as lm')
                            ->select(['s.id as station_id'])
                            ->join(DB::raw('lines as l'), 'l.id', '=', 'lm.lines_id')
                            ->join(DB::raw('maintains as s'), 's.id', '=', 'lm.maintains_id')
                            ->where('l.unique_code', request('line_unique_code'))
                            ->pluck('station_id')
                            ->toArray();
                        $query->whereIn('id', $station_ids);
                    }
                )
                ->get()
                ->toArray();

            return HttpResponseHelper::data($stations);
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 获取车间
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function getWorkshop($lineId)
    {
        try {
            $maintainsId = DB::table('lines_maintains')->where('lines_id', $lineId)->get(['maintains_id']);
            if (count($maintainsId) > 0) {
                foreach ($maintainsId as $workshopId) {
                    $workshop[] = DB::table('maintains')->where('id', $workshopId->maintains_id)->get();
                }
                return ['data' => $workshop, 'status' => 200];
            } else {
                return ['data' => '数据不存在', 'status' => 422];
            }
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误' . $exceptionMessage, 500);
        }
    }
}
