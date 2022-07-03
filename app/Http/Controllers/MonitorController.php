<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\WorkArea;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;

class MonitorController extends Controller
{
    final public function index()
    {
        try {
            $categories = DB::table('categories')->get(['name', 'id', 'unique_code'])->toArray();
            $maintains = DB::table('maintains as sc')
                ->selectRaw('sc.name as scene_workshop_name, sc.unique_code as scene_workshop_unique_code, s.unique_code as station_unique_code, s.name as station_name')
                ->leftJoin(DB::raw('maintains s'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->where('s.type', 'STATION')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->get();
            $maintainStatistics = [];
            $sceneWorkshopPoints = [];

            foreach ($maintains as $maintain) {
                if (!array_key_exists($maintain->scene_workshop_unique_code, $maintainStatistics)) {
                    $sceneWorkshops[$maintain->scene_workshop_unique_code] = $maintain->scene_workshop_name;
                    $maintainStatistics[$maintain->scene_workshop_unique_code] = [
                        'unique_code' => $maintain->scene_workshop_unique_code,
                        'name' => $maintain->scene_workshop_name,
                        'stations' => [],
                    ];
                }
                if (!empty($maintain->station_unique_code)) {
                    $maintainStatistics[$maintain->scene_workshop_unique_code]['stations'][$maintain->station_unique_code] = [
                        'unique_code' => $maintain->station_unique_code,
                        'name' => $maintain->station_name
                    ];
                }
            }
            # 车站标点
            $stationPoints = [];
            $stations = DB::table('maintains')
                ->where('type', 'STATION')
                ->whereNull('deleted_at')
                ->where('lon', '<>', '')
                ->where('lat', '<>', '')
                ->get();
            foreach ($stations as $station) {
                if (!array_key_exists($station->unique_code, $stationPoints)) {
                    $stationPoints[$station->unique_code] = [
                        'lon' => $station->lon,
                        'lat' => $station->lat,
                        'name' => $station->name,
                        'contact' => $station->contact,
                        'contact_phone' => $station->contact_phone,
                        'contact_address' => $station->contact_address,
                        'scene_workshop_unique_code' => $station->parent_unique_code,
                    ];
                }
            }
            # 车间标点
            $workshops = DB::table('maintains')
                ->whereNull('deleted_at')
                ->where('parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('lon', '<>', '')
                ->where('lat', '<>', '')
                ->get();
            foreach ($workshops as $workshop) {
                if (!array_key_exists($workshop->unique_code, $sceneWorkshopPoints)) {
                    $sceneWorkshopPoints[$workshop->unique_code] = [
                        'lon' => $workshop->lon,
                        'lat' => $workshop->lat,
                        'name' => $workshop->name,
                        'contact' => $workshop->contact,
                        'contact_phone' => $workshop->contact_phone,
                        'contact_address' => $workshop->contact_address,
                    ];
                }
            }

            $deviceDB = DB::table("entire_instances")
                ->where("deleted_at", null)
                ->where(
                    "category_unique_code",
                    request('categoryUniqueCode', 'S03')
                );
            $using = $deviceDB->whereIn("status", ["INSTALLING", "INSTALLED"])->count("id");
            $fixed = $deviceDB->where("status", "FIXED")->count("id");
            $returnFactory = $deviceDB->where("status", "RETURN_FACTORY")->count("id");
            $fixing = $deviceDB->whereIn("status", ["FIXING", "FACTORY_RETURN", "BUY_IN"])->count("id");
            $total = $deviceDB->where("status", "<>", "SCRAP")->count("id");

            $deviceDynamics_iframe = [
                'total' => $total,
                'status' => [
                    ["name" => "上道使用", "value" => $using],
                    ["name" => "待修", "value" => $fixing],
                    ["name" => "送检中", "value" => $returnFactory],
                    ["name" => "所内备品", "value" => $fixed],
                ]
            ];
            # 车站连线
            $linePoint = DB::table('line_points')->where('organization_code', env('ORGANIZATION_CODE'))->select('center_point', 'points')->first();

            return view('Index.monitor', [
                'categories' => $categories,
                'categories_iframe' => TextHelper::toJson($categories),
                'sceneWorkshopPoints' => TextHelper::toJson($sceneWorkshopPoints),
                'linePoints' => $linePoint->points,
                'centerPoint' => $linePoint->center_point,
                'stationPoints' => TextHelper::toJson($stationPoints),
                'deviceDynamics_iframe' => TextHelper::toJson($deviceDynamics_iframe),
                'maintainStatistics' => json_encode($maintainStatistics)
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger','意外错误');
        }
    }
}
