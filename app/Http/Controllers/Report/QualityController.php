<?php

namespace App\Http\Controllers\Report;

use App\Facades\CommonFacade;
use App\Facades\KindsFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Facades\ReportFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Serializers\BreakdownSerializer;
use App\Serializers\EntireInstanceSerializer;
use App\Serializers\StationSerializer;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\FileSystem;
use Jericho\TextHelper;

class QualityController extends Controller
{
    private $_organizationCode = null;
    private $_organizationName = null;
    private $_qualityDir = '';
    private $_qualityTypeDir = '';
    private $_deviceDir = '';
    private $_propertyDir = '';
    private $_basicInfoDir = '';
    private $_quarters = [];
    private $_current_year = '';


    public function __construct()
    {
        $this->_organizationCode = env('ORGANIZATION_CODE');
        $this->_organizationName = env('ORGANIZATION_NAME');
        $this->_qualityDir = 'app/quality/breakdownDevice';
        $this->_qualityTypeDir = 'app/quality/breakdownType';
        $this->_basicInfoDir = 'app/basicInfo';
        $this->_deviceDir = 'app/basicInfo/deviceTotal';
        $this->_propertyDir = 'app/property';

        $this->_quarters = [
            '1季度' => 'Q1',
            '2季度' => 'Q2',
            '3季度' => 'Q3',
            '4季度' => 'Q4',
        ];
        $this->_current_year = date('Y');
    }

    /**
     * 质量报告
     * @param Request $request
     * @return Factory|Application|RedirectResponse|Redirector|View|string
     */
    final public function quality(Request $request)
    {
        try {
            // 获取设备器材统计
            $db_device_statistics_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
                EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipS(true)
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'ei.model_unique_code as model_unique_code',
                    'ei.model_name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);

            $db_device_statistics_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
                EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(sm.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'sm.unique_code as model_unique_code',
                    'sm.name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);
            $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

            $file = FileSystem::init(__FILE__);
            $quality_years = $file->setPath(storage_path($this->_qualityDir))->join('yearList.json')->fromJson();
            $quality_months = $file->setPath(storage_path($this->_qualityDir))->join('dateList.json')->fromJson();
            $quality_date_type = request('dateType', 'year');
            switch ($quality_date_type) {
                case 'year':
                    $year = request('date', date('Y'));
                    $origin_at = Carbon::createFromDate($year)->startOfYear();
                    $finish_at = Carbon::createFromDate($year)->endOfYear();

                    break;
                case 'month':
                    $origin_at = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->startOfMonth();
                    $finish_at = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->endOfMonth();
                    break;
                default:
                    $origin_at = Carbon::now()->startOfYear();
                    $finish_at = Carbon::now()->endOfMonth();
                    break;
            }

            $db_breakdown_statistics_S = BreakdownSerializer::INIT([
                BreakdownSerializer::$IS_INCLUDE_STATION => true,
                BreakdownSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                BreakdownSerializer::$IS_PART => false,
                BreakdownSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            ])
                ->generateStandardRelationshipS()
                ->selectRaw(implode(',', [
                    'count(s.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'ei.model_unique_code as model_unique_code',
                    'ei.model_name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    's.unique_code as station_unique_code',
                    's.name as station_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'sc.unique_code', 'sc.name', 's.unique_code', 's.name', 'f.unique_code', 'f.name',]);
            $db_breakdown_statistics_Q = BreakdownSerializer::INIT([
                BreakdownSerializer::$IS_INCLUDE_STATION => true,
                BreakdownSerializer::$IS_HARD_SCENE_WORKSHOP_RELATIONSHIP => true,
                BreakdownSerializer::$IS_HARD_STATION_RELATIONSHIP => true,
                BreakdownSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            ])
                ->generateStandardRelationshipQ()
                ->selectRaw(implode(',', [
                    'count(s.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'sm.unique_code as model_unique_code',
                    'sm.name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    's.unique_code as station_unique_code',
                    's.name as station_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'sc.unique_code', 'sc.name', 's.unique_code', 's.name', 'f.unique_code', 'f.name',]);
            $breakdown_statistics = QueryBuilderFacade::unionAll($db_breakdown_statistics_S, $db_breakdown_statistics_Q)->get();

            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityYear = $dirs['qualityYear'];
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $qualityTypeDir = $dirs['qualityTypeDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityYearList = $dirs['qualityYearList'];
            $quality_dates = $dirs['qualityDateList'];

            return view('Report.Quality.quality', [
                'device_statistics_as_json' => $device_statistics->toJson(),
                'breakdown_statistics_as_json' => $breakdown_statistics->toJson(),
                'quality_date_type' => $quality_date_type,
                'quality_date' => request('date', date('Y-m')),
                'quality_years' => $quality_years,
                'quality_months' => $quality_months,
                'quality_dates' => $quality_dates,
                'quality_year' => request('qualityYear', date('Y')),
            ]);
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取质量报告所需路径
     * @param string $qualityDateType
     * @return array
     * @throws Exception
     */
    public function getQualityDirs(string $qualityDateType)
    {
        $qualityDate = '';
        $qualityDir = '';
        $qualityTypeDir = '';
        $deviceDir = '';
        $propertyDir = '';
        $qualityYearList = [];
        $qualityDateList = [];
        $file = FileSystem::init('');
        if ($qualityDateType == 'year') {
            $qualityYear = request("qualityYear", date("Y"));
            $year = $qualityDate = request("qualityDate", date("Y"));
            $qualityYearList = $file->setPath(storage_path($this->_qualityDir))->join("yearList.json")->fromJson();
            $qualityDir = storage_path($this->_qualityDir . "/{$year}");
            $qualityTypeDir = storage_path($this->_qualityTypeDir . "/{$year}");
            $deviceDir = storage_path($this->_deviceDir . "/{$year}");
            $propertyDir = storage_path($this->_propertyDir . "/{$year}");
        }
        if ($qualityDateType == 'month') {
            $qualityYear = request("qualityYear", date("Y"));
            $qualityDateList = $file->setPath(storage_path($this->_qualityDir))->join("dateList.json")->fromJson();
            $qualityDate = request("qualityDate", date("Y-m"));
            list($year, $month) = explode("-", $qualityDate);
            $qualityDir = storage_path($this->_qualityDir . "/{$year}/{$year}-{$month}");
            $qualityTypeDir = storage_path($this->_qualityTypeDir . "/{$year}/{$year}-{$month}");
            $deviceDir = storage_path($this->_deviceDir . "/{$year}/{$year}-{$month}");
            $propertyDir = storage_path($this->_propertyDir . "/{$year}/{$year}-{$month}");
        }
        if ($qualityDateType == 'quarter') {
            $qualityYear = request("qualityYear", date("Y"));
            $qualityYearList = $file->setPath(storage_path($this->_qualityDir))->join("yearList.json")->fromJson();
            $qualityDate = request("qualityDate", ceil(date('n') / 3) . '季度');
            $qualityDateList = array_keys($this->_quarters);
            $qualityDir = storage_path($this->_qualityDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
            $qualityTypeDir = storage_path($this->_qualityTypeDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
            $deviceDir = storage_path($this->_deviceDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
            $propertyDir = storage_path($this->_propertyDir . "/{$this->_current_year}/{$this->_quarters[$qualityDate]}");
        }
        return [
            'qualityYear' => $qualityYear,
            'qualityDate' => $qualityDate,
            'qualityDir' => $qualityDir,
            'qualityTypeDir' => $qualityTypeDir,
            'deviceDir' => $deviceDir,
            'propertyDir' => $propertyDir,
            'qualityYearList' => $qualityYearList,
            'qualityDateList' => $qualityDateList,
            'file' => $file
        ];

    }

    /**
     * 质量报告 - 种类
     * @param Request $request
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualityCategory(Request $request, string $categoryUniqueCode)
    {
        try {
            // 获取设备器材统计
            // $db_device_statistics_S = EntireInstanceSerializer::INS([
            //     EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            // ])
            //     ->generateQueryRelationShipS()
            //     ->selectRaw(implode(',', [
            //         'count(ei.factory_name) as aggregate',
            //         'c.unique_code as category_unique_code',
            //         'c.name as category_name',
            //         'em.unique_code as entire_model_unique_code',
            //         'em.name as entire_model_name',
            //         'ei.model_unique_code as model_unique_code',
            //         'ei.model_name as model_name',
            //         'f.unique_code as factory_unique_code',
            //         'f.name as factory_name',
            //     ]))
            //     ->where('c.unique_code', $categoryUniqueCode)
            //     ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'f.unique_code', 'f.name',]);
            //
            // $db_device_statistics_Q = EntireInstanceSerializer::INS([
            //     EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            // ])
            //     ->generateQueryRelationShipQ()
            //     ->selectRaw(implode(',', [
            //         'count(ei.factory_name) as aggregate',
            //         'c.unique_code as category_unique_code',
            //         'c.name as category_name',
            //         'em.unique_code as entire_model_unique_code',
            //         'em.name as entire_model_name',
            //         'ei.model_unique_code as model_unique_code',
            //         'ei.model_name as model_name',
            //         'f.unique_code as factory_unique_code',
            //         'f.name as factory_name',
            //     ]))
            //     ->where('c.unique_code', $categoryUniqueCode)
            //     ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'f.unique_code', 'f.name',]);
            // $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

            // 获取设备器材统计
            $db_device_statistics_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
                EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'ei.model_unique_code as model_unique_code',
                    'ei.model_name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);

            $db_device_statistics_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
                EntireInstanceSerializer::$IS_HARD_FACTORY_RELATIONSHIP => true,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(sm.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'sm.unique_code as model_unique_code',
                    'sm.name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);
            $device_statistics = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->get();

            $file = FileSystem::init(__FILE__);
            $quality_years = $file->setPath(storage_path($this->_qualityDir))->join('yearList.json')->fromJson();
            $quality_months = $file->setPath(storage_path($this->_qualityDir))->join('dateList.json')->fromJson();
            $quality_date_type = request('dateType', 'year');
            switch ($quality_date_type) {
                case 'year':
                    $year = request('date', date('Y'));
                    $origin_at = Carbon::createFromDate($year)->startOfYear();
                    $finish_at = Carbon::createFromDate($year)->endOfYear();
                    break;
                case 'month':
                    $origin_at = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->startOfMonth();
                    $finish_at = Carbon::createFromFormat('Y-m', request('date', date('Y-m')))->endOfMonth();
                    break;
                default:
                    $origin_at = Carbon::now()->startOfYear();
                    $finish_at = Carbon::now()->endOfMonth();
                    break;
            }
            $db_breakdown_statistics_S = BreakdownSerializer::INIT([
                BreakdownSerializer::$IS_INCLUDE_STATION => true,
            ])
                ->generateStandardRelationshipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'ei.model_unique_code as model_unique_code',
                    'ei.model_name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'ei.model_unique_code', 'ei.model_name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);
            $db_breakdown_statistics_Q = BreakdownSerializer::INIT([
                BreakdownSerializer::$IS_INCLUDE_STATION => true,
            ])
                ->generateStandardRelationshipQ()
                ->selectRaw(implode(',', [
                    'count(sm.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                    'em.unique_code as entire_model_unique_code',
                    'em.name as entire_model_name',
                    'sm.unique_code as model_unique_code',
                    'sm.name as model_name',
                    'sc.unique_code as scene_workshop_unique_code',
                    'sc.name as scene_workshop_name',
                    'f.unique_code as factory_unique_code',
                    'f.name as factory_name',
                ]))
                ->whereBetween('rbboei.created_at', [$origin_at, $finish_at])
                ->groupBy(['c.unique_code', 'c.name', 'em.unique_code', 'em.name', 'sm.unique_code', 'sm.name', 'sc.unique_code', 'sc.name', 'f.unique_code', 'f.name',]);
            $breakdown_statistics = QueryBuilderFacade::unionAll($db_breakdown_statistics_S, $db_breakdown_statistics_Q)->get();

            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityYear = $dirs['qualityYear'];
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $qualityTypeDir = $dirs['qualityTypeDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityYearList = $dirs['qualityYearList'];
            $quality_dates = $dirs['qualityDateList'];

            return view('Report.Quality.qualityCategory', [
                'device_statistics_as_json' => $device_statistics->toJson(),
                'breakdown_statistics_as_json' => $breakdown_statistics->toJson(),
                'quality_date_type' => $quality_date_type,
                'quality_date' => request('date', date('Y-m')),
                'quality_years' => $quality_years,
                'quality_months' => $quality_months,
                'quality_dates' => $quality_dates,
                'quality_year' => request('qualityYear', date('Y')),
            ]);
        } catch (Exception $e) {
            // dd($e->getTrace());
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 现场车间
     * @param Request $request
     * @param string $sceneWorkshopUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualitySceneWorkshop(Request $request, string $sceneWorkshopUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $stations = $file->setPath(storage_path($this->_basicInfoDir))->join('stations.json')->fromJson();
            $deviceWithMaintains = $file->setPath($deviceDir)->join('devicesAsMaintain.json')->fromJson();
            $breakdownWithMaintains = $file->setPath($qualityDir)->join('maintain.json')->fromJson();
            $deviceWithStations = $deviceWithMaintains[$sceneWorkshopUniqueCode]['subs'] ?? [];
            $breakdownWithStations = $breakdownWithMaintains[$sceneWorkshopUniqueCode]['subs'] ?? [];
            $deviceWithFactories = $deviceWithMaintains[$sceneWorkshopUniqueCode]['factories'] ?? [];
            $breakdownWithFactories = $breakdownWithMaintains[$sceneWorkshopUniqueCode]['factories'] ?? [];

            return view("Report.Quality.qualitySceneWorkshop", [
                'qualityDateType' => $qualityDateType,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'sceneWorkshopName' => $stations[$sceneWorkshopUniqueCode]['name'],
                'sceneWorkshopUniqueCode' => $sceneWorkshopUniqueCode,
                'deviceWithStations' => TextHelper::toJson($deviceWithStations),
                'breakdownWithStations' => TextHelper::toJson($breakdownWithStations),
                'deviceWithFactories' => TextHelper::toJson($deviceWithFactories),
                'breakdownWithFactories' => TextHelper::toJson($breakdownWithFactories),
            ]);

        } catch (Exception $e) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 现场车间 - 车站
     * @param Request $request
     * @param string $sceneWorkshopUniqueCode
     * @param string $stationUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualityStation(Request $request, string $sceneWorkshopUniqueCode, string $stationUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityDate = $dirs['qualityDate'];
            $qualityDir = $dirs['qualityDir'];
            $deviceDir = $dirs['deviceDir'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $stations = $file->setPath(storage_path($this->_basicInfoDir))->join('stations.json')->fromJson();
            $deviceWithMaintains = $file->setPath($deviceDir)->join('devicesAsMaintain.json')->fromJson();
            $breakdownWithMaintains = $file->setPath($qualityDir)->join('maintain.json')->fromJson();
            $deviceWithFactories = $deviceWithMaintains[$sceneWorkshopUniqueCode]['subs'][$stationUniqueCode]['factories'] ?? [];
            $breakdownWithFactories = $breakdownWithMaintains[$sceneWorkshopUniqueCode]['subs'][$stationUniqueCode]['factories'] ?? [];

            return view("Report.Quality.qualityStation", [
                'qualityDateType' => $qualityDateType,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'sceneWorkshopName' => $stations[$sceneWorkshopUniqueCode]['name'],
                'sceneWorkshopUniqueCode' => $sceneWorkshopUniqueCode,
                'stationUniqueCode' => $stationUniqueCode,
                'stationName' => $stations[$sceneWorkshopUniqueCode]['subs'][$stationUniqueCode]['name'],
                'deviceWithFactories' => TextHelper::toJson($deviceWithFactories),
                'breakdownWithFactories' => TextHelper::toJson($breakdownWithFactories),
            ]);
        } catch (Exception $e) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 种类 - 故障类型
     * @param Request $request
     * @param string $categoryUniqueCode
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    final public function qualityBreakdownTypeWithCategory(Request $request, string $categoryUniqueCode)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', 'year');
            $dirs = $this->getQualityDirs($qualityDateType);
            $qualityDate = $dirs['qualityDate'];
            $qualityTypeDir = $dirs['qualityTypeDir'];
            $qualityDateList = $dirs['qualityDateList'];
            $file = $dirs['file'];
            $kinds = $file->setPath(storage_path($this->_basicInfoDir))->join('kinds.json')->fromJson();
            $breakdownTypeWithCategories = $file->setPath($qualityTypeDir)->join('kind.json')->fromJson();
            $breakdownTypeWithFactories = $breakdownTypeWithCategories[$categoryUniqueCode]['factories'] ?? [];

            return view("Report.Quality.qualityBreakdownTypeWithCategory", [
                'categoryUniqueCode' => $categoryUniqueCode,
                'categoryName' => $kinds[$categoryUniqueCode]['name'],
                'qualityDateType' => $qualityDateType,
                'qualityDateList' => $qualityDateList,
                'qualityDate' => $qualityDate,
                'breakdownTypeWithFactories' => TextHelper::toJson($breakdownTypeWithFactories)
            ]);

        } catch (Exception $exception) {
            return back()->with("info", '暂无数据');
        }
    }

    /**
     * 质量报告 - 设备列表
     * @param Request $request
     * @return Factory|Application|RedirectResponse|View
     */
    final public function qualityEntireInstance(Request $request)
    {
        try {
            $qualityDateType = $request->get('qualityDateType', '');
            $qualityDate = $request->get('qualityDate', '');
            $repairAt = ReportFacade::handleDateWithType($qualityDateType, $qualityDate);

            $statuses = collect(EntireInstance::$STATUSES);
            $factories = @\App\Model\Factory::with([])->get() ?: collect([]);
            $categories = KindsFacade::getCategories([], function ($db) {
                return $db->where("is_show", true);
            });
            $entire_models = KindsFacade::getEntireModelsByCategory();
            $models = KindsFacade::getModelsByEntireModel();
            $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
                return $db->where("sc.is_show", true);
            });
            $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) {
                return $db->where("s.is_show", true);
            });

            $currentSelRepairAt = $request->get('selRepairAt', '');
            $currentRepairAt = $request->get('repairAt', '');
            if (!empty($repairAt)) {
                $currentSelRepairAt = 1;
                $currentRepairAt = $repairAt;
            }
            list($currentRepairAtOrigin, $currentRepairAtFinish) = explode("~", empty($currentRepairAt) ? Carbon::now()->startOfDay()->toDateTimeString() . '~' . Carbon::now()->endOfDay()->toDateTimeString() : $currentRepairAt);

            $categories = KindsFacade::getCategories([], function ($db) {
                return $db->where("is_show", true);
            });
            $entire_models = KindsFacade::getEntireModelsByCategory();
            $models = KindsFacade::getModelsByEntireModel();
            $factories = DB::table('factories as f')->whereNull('f.deleted_at')->get();
            $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
                return $db->where("sc.is_show", true);
            });
            $lines = OrganizationFacade::getLines([], function ($db) {
                return $db->where("is_show", true);
            });
            $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) {
                return $db->where("s.is_show", true);
            });

            switch (request('type')) {
                case 'breakdown':
                    $db_S = BreakdownSerializer::INIT([
                        EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                    ])
                        ->generateStandardRelationshipS()
                        ->selectRaw(implode(',', [
                            'rbboei.old_entire_instance_identity_code as identity_code',
                            'c.name as category_name',
                            'em.name as entire_model_name',
                            'pm.name as sub_model_name',
                            'rbbo.updated_at as repair_updated_at',
                            'ei.status as status',
                            'f.name as factory_name',
                            'rbboei.maintain_station_name as maintain_station_name',
                            'rbboei.open_direction as open_direction',
                            'rbboei.said_rod as said_rod',
                            'rbboei.crossroad_number as crossroad_number',
                            'ei.line_name as line_name',
                            'rbboei.maintain_location_code as maintain_location_code',
                        ]))
                        ->when(
                            $currentSelRepairAt == 1,
                            function ($query) use ($currentRepairAtOrigin, $currentRepairAtFinish) {
                                return $query->whereBetween('rbbo.updated_at', [$currentRepairAtOrigin, $currentRepairAtFinish]);
                            }
                        )
                        ->groupBy(['rbboei.old_entire_instance_identity_code'])
                        ->orderByDesc('rbboei.updated_at');

                    $db_Q = BreakdownSerializer::INIT([
                        EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                    ])
                        ->generateStandardRelationshipQ()
                        ->selectRaw(implode(',', [
                            'rbboei.old_entire_instance_identity_code as identity_code',
                            'c.name as category_name',
                            'em.name as entire_model_name',
                            'sm.name as sub_model_name',
                            'rbbo.updated_at as repair_updated_at',
                            'ei.status as status',
                            'f.name as factory_name',
                            'rbboei.maintain_station_name as maintain_station_name',
                            'rbboei.open_direction as open_direction',
                            'rbboei.said_rod as said_rod',
                            'rbboei.crossroad_number as crossroad_number',
                            'ei.line_name as line_name',
                            'rbboei.maintain_location_code as maintain_location_code',
                        ]))
                        ->when(
                            $currentSelRepairAt == 1,
                            function ($query) use ($currentRepairAtOrigin, $currentRepairAtFinish) {
                                return $query->whereBetween('rbbo.updated_at', [$currentRepairAtOrigin, $currentRepairAtFinish]);
                            }
                        )
                        ->groupBy(['rbboei.old_entire_instance_identity_code'])
                        ->orderByDesc('rbboei.updated_at');

                    $entire_instances = QueryBuilderFacade::unionAll($db_S, $db_Q)->paginate();
                    return view("Report.Quality.qualityEntireInstance_breakdown", [
                        'statuses_as_json' => $statuses->toJson(),
                        'factories_as_json' => $factories->toJson(),
                        'categories_as_json' => $categories->toJson(),
                        'entire_models_as_json' => $entire_models->toJson(),
                        'models_as_json' => $models->toJson(),
                        'scene_workshops_as_json' => $scene_workshops->toJson(),
                        'stations_as_json' => $stations->toJson(),
                        'statuses' => EntireInstance::$STATUSES,
                        'entire_instances' => $entire_instances,
                        'currentRepairAt' => $currentRepairAt,
                        'currentSelRepairAt' => $currentSelRepairAt,
                        'currentRepairAtOrigin' => $currentRepairAtOrigin,
                        'currentRepairAtFinish' => $currentRepairAtFinish,
                    ]);
                case 'device':
                    $now = now()->format('Y-m-d');
                    list($dateMadeAtOrigin, $dateMadeAtFinish) = explode('~', request('date_made_at', '{$now} 00:00:00~{$now} 23:59:59'));
                    list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode('~', request('date_created_at', '{$now} 00:00:00~{$now} 23:59:59'));
                    list($dateNextFixingDayOrigin, $dateNextFixingDayFinish) = explode('~', request('date_next_fixing_day', '{$now} 00:00:00~{$now} 23:59:59'));

                    $statuses = collect(EntireInstance::$STATUSES);
                    $factories = @\App\Model\Factory::with([])->get() ?: collect([]);
                    $categories = KindsFacade::getCategories([], function ($db) {
                        return $db->where("is_show", true);
                    });
                    $entire_models = KindsFacade::getEntireModelsByCategory();
                    $models = KindsFacade::getModelsByEntireModel();
                    $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
                        return $db->where("sc.is_show", true);
                    });
                    $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) {
                        return $db->where("s.is_show", true);
                    });

                    $db_device_statistics_S = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                    ])
                        ->GenerateQueryRelationShipS()
                        ->selectRaw(implode(',', [
                            'ei.identity_code',
                            'ei.factory_name',
                            'ei.maintain_location_code',
                            'ei.crossroad_number',
                            'ei.open_direction',
                            'ei.said_rod',
                            'ei.installed_at',
                            'ei.last_fix_workflow_at',
                            'ei.next_fixing_time',
                            'ei.scarping_at',
                            'ei.model_name',
                            'ei.model_unique_code',
                            'em.unique_code as entire_model_unique_code',
                            'em.category_unique_code as category_unique_code',
                            'ei.status',
                            'ei.fix_cycle_value as ei_fix_cycle_value',
                            'em.fix_cycle_value as em_fix_cycle_value',
                        ]));
                    $db_device_statistics_Q = EntireInstanceSerializer::ins([
                        EntireInstanceSerializer::$IS_INCLUDE_STATION => true,
                    ])
                        ->GenerateQueryRelationShipQ()
                        ->selectRaw(implode(',', [
                            'ei.identity_code',
                            'ei.factory_name',
                            'ei.maintain_location_code',
                            'ei.crossroad_number',
                            'ei.open_direction',
                            'ei.said_rod',
                            'ei.installed_at',
                            'ei.last_fix_workflow_at',
                            'ei.next_fixing_time',
                            'ei.scarping_at',
                            'ei.model_name',
                            'ei.model_unique_code',
                            'em.unique_code as entire_model_unique_code',
                            'em.category_unique_code as category_unique_code',
                            'ei.status',
                            'ei.fix_cycle_value as ei_fix_cycle_value',
                            'em.fix_cycle_value as em_fix_cycle_value',
                        ]));
                    $entire_instances = QueryBuilderFacade::unionAll($db_device_statistics_S, $db_device_statistics_Q)->paginate(30);

                    return view('Entire.Instance.index2', [
                        'statuses_as_json' => $statuses->toJson(),
                        'factories_as_json' => $factories->toJson(),
                        'categories_as_json' => $categories->toJson(),
                        'entire_models_as_json' => $entire_models->toJson(),
                        'models_as_json' => $models->toJson(),
                        'scene_workshops_as_json' => $scene_workshops->toJson(),
                        'stations_as_json' => $stations->toJson(),
                        'statuses' => EntireInstance::$STATUSES,
                        'entire_instances' => $entire_instances,
                        'dateMadeAtOrigin' => $dateMadeAtOrigin,
                        'dateMadeAtFinish' => $dateMadeAtFinish,
                        'dateCreatedAtOrigin' => $dateCreatedAtOrigin,
                        'dateCreatedAtFinish' => $dateCreatedAtFinish,
                        'dateNextFixingDayOrigin' => $dateNextFixingDayOrigin,
                        'dateNextFixingDayFinish' => $dateNextFixingDayFinish,
                    ]);
                default:
                    return back()->with('danger', '链接参数错误');
            }
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('info', '暂无数据');
        }
    }
}
