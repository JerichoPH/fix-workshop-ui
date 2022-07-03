<?php

namespace App\Http\Controllers\V1;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\QrCodeFacade;
use App\Facades\QueryBuilderFacade;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\Install\InstallPosition;
use App\Model\Line;
use App\Model\Maintain;
use App\Serializers\BreakdownSerializer;
use App\Serializers\EntireInstanceSerializer;
use App\Services\EntireInstanceLogService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\Model\Log;
use Throwable;
use function MongoDB\BSON\fromJSON;

class BIController extends Controller
{
    /**
     * 设备数量和超期数量统计
     * @return mixed
     */
    final public function getFacilityAndOverdueStatistics()
    {
        try {
            // 设备统计
            $total_statistics = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate'
                    , 'c.unique_code as category_unique_code'
                    , 'c.name as category_name'
                ]))
                ->groupBy([
                    'c.unique_code'
                    , 'c.name'
                ])
                ->where('c.unique_code', 'like', 'S%')
                ->get();

            // 超期统计
            $overdue_statistics = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate'
                    , 'c.unique_code as category_unique_code'
                    , 'c.name as category_name'
                ]))
                ->groupBy([
                    'c.unique_code'
                    , 'c.name'
                ])
                ->where('c.unique_code', 'like', 'S%')
                ->where('ei.scarping_at', '<', now()->format('Y-m-d H:i:s'))
                ->get();

            $statistics = [];
            $total_statistics->each(function ($total_statistic)
            use (
                $total_statistics,
                $overdue_statistics,
                &$statistics
            ) {
                if (!@$statistics[$total_statistic->category_unique_code]) $statistics[$total_statistic->category_unique_code] = ['name' => $total_statistic->category_name, 'total' => 0, 'overdue' => 0,];
                $statistics[$total_statistic->category_unique_code]['total'] += $total_statistic->aggregate;
            });

            $overdue_statistics->each(function ($overdue_statistic)
            use (
                $total_statistics,
                $overdue_statistics,
                &$statistics
            ) {
                if (!@$statistics[$overdue_statistic->category_unique_code]) $statistics[$overdue_statistic->category_unique_code] = ['name' => $overdue_statistic->category_name, 'total' => 0, 'overdue' => 0,];
                $statistics[$overdue_statistic->category_unique_code]['overdue'] += $overdue_statistic->aggregate;
            });

            return JsonResponseFacade::dict([
                'statistics' => array_values($statistics)
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 器材数量和超期数量统计
     * @return mixed
     */
    final public function getEquipmentAndOverdueStatistics()
    {
        try {
            $line_unique_code = request('line_unique_code');
            $station_names = [];
            if ($line_unique_code) {
                if (!Line::with([])->where('unique_code', $line_unique_code)->exists()) return JsonResponseFacade::errorValidate('线别不存在');
                $station_names = DB::table('lines_maintains as lm')
                    ->select(['s.name'])
                    ->join(DB::raw('maintains s'), 'lm.maintains_id', '=', 's.id')
                    ->join(DB::raw('`lines` l'), 'lm.lines_id', '=', 'l.id')
                    ->where('l.unique_code', $line_unique_code)
                    ->get()
                    ->pluck('name')
                    ->toArray();
            }

            $station_unique_code = request('station_unique_code');
            if ($station_unique_code) {
                $station = Maintain::with([])->select(['name'])->where('unique_code', $station_unique_code)->where('type', 'STATION')->first();
                $station_names = [$station->name];
            }

            // 设备统计
            $total_statistics = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name',])
                ->where('c.unique_code', 'like', 'Q%')
                ->when($station_names, function ($query, $station_names) {
                    $query->whereIn('ei.maintain_station_name', $station_names);
                })
                ->get();

            // 超期统计
            $overdue_statistics = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(c.unique_code) as aggregate',
                    'c.unique_code as category_unique_code',
                    'c.name as category_name',
                ]))
                ->groupBy(['c.unique_code', 'c.name',])
                ->where('c.unique_code', 'like', 'Q%')
                ->where('ei.scarping_at', '<', now()->format('Y-m-d H:i:s'))
                ->when($station_names, function ($query, $station_names) {
                    $query->whereIn('ei.maintain_station_name', $station_names);
                })
                ->get();

            $statistics = [];
            $total_statistics->each(function ($total_statistic)
            use (
                $total_statistics,
                $overdue_statistics,
                &$statistics
            ) {
                if (!@$statistics[$total_statistic->category_unique_code]) $statistics[$total_statistic->category_unique_code] = ['name' => $total_statistic->category_name, 'total' => 0, 'overdue' => 0,];
                $statistics[$total_statistic->category_unique_code]['total'] += $total_statistic->aggregate;
            });

            $overdue_statistics->each(function ($overdue_statistic)
            use (
                $total_statistics,
                $overdue_statistics,
                &$statistics
            ) {
                if (!@$statistics[$overdue_statistic->category_unique_code]) $statistics[$overdue_statistic->category_unique_code] = ['name' => $overdue_statistic->category_name, 'total' => 0, 'overdue' => 0,];
                $statistics[$overdue_statistic->category_unique_code]['overdue'] += $overdue_statistic->aggregate;
            });

            return JsonResponseFacade::dict(['statistics' => array_values($statistics),]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备、器材备品和故障统计
     */
    final public function getStandbyAndBreakdownStatistics()
    {
        try {
            $line_unique_code = request('line_unique_code');
            $station_names = [];
            if ($line_unique_code) {
                if (!Line::with([])->where('unique_code', $line_unique_code)->exists()) return JsonResponseFacade::errorValidate('线别不存在');
                $station_names = DB::table('lines_maintains as lm')
                    ->select(['s.name'])
                    ->join(DB::raw('maintains s'), 'lm.maintains_id', '=', 's.id')
                    ->join(DB::raw('`lines` l'), 'lm.lines_id', '=', 'l.id')
                    ->where('l.unique_code', $line_unique_code)
                    ->get()
                    ->pluck('name')
                    ->toArray();
            }

            $station_unique_code = request('station_unique_code');
            if ($station_unique_code) {
                $station = Maintain::with([])->select(['name'])->where('unique_code', $station_unique_code)->where('type', 'STATION')->first();
                $station_names = [$station->name];
            }

            // 备品统计
            $standby_statistics_S = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate'
                    , 'c.unique_code as category_unique_code'
                    , 'c.name as category_name'
                ]))
                ->groupBy([
                    'c.unique_code'
                    , 'c.name'
                ])
                ->whereIn('ei.status', ['INSTALLING', 'FIXED']);
            $standby_statistics_Q = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate'
                    , 'c.unique_code as category_unique_code'
                    , 'c.name as category_name'
                ]))
                ->groupBy([
                    'c.unique_code'
                    , 'c.name'
                ])
                ->whereIn('ei.status', ['INSTALLING', 'FIXED']);

            // 故障统计
            $breakdown_statistics_S = DB::table('breakdown_logs as bl')
                ->selectRaw(join(',', [
                    'count(bl.entire_instance_identity_code) as aggregate'
                    , 'bl.entire_instance_identity_code'
                    , 'c.unique_code as category_unique_code'
                    , 'c.name as category_name'
                ]))
                ->join(DB::raw('entire_instances ei'), 'bl.entire_instance_identity_code', '=', 'ei.identity_code')
                ->join(DB::raw('entire_models em'), 'ei.entire_model_unique_code', '=', 'em.unique_code')
                ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                ->groupBy([
                    'bl.entire_instance_identity_code'
                    , 'c.unique_code'
                    , 'c.name'
                ])
                ->where('c.unique_code', 'like', 'S%')
                ->when($station_names, function ($query, $station_names) {
                    $query->whereIn('bl.maintain_station_name', $station_names);
                });

            $breakdown_statistics_Q = DB::table('breakdown_logs as bl')
                ->selectRaw(join(',', [
                    'count(bl.entire_instance_identity_code) as aggregate'
                    , 'bl.entire_instance_identity_code'
                    , 'c.unique_code as category_unique_code'
                    , 'c.name as category_name'
                ]))
                ->join(DB::raw('entire_instances ei'), 'bl.entire_instance_identity_code', '=', 'ei.identity_code')
                ->join(DB::raw('entire_models sm'), 'ei.model_unique_code', '=', 'sm.unique_code')
                ->join(DB::raw('entire_models em'), 'sm.parent_unique_code', '=', 'em.unique_code')
                ->join(DB::raw('categories c'), 'em.category_unique_code', '=', 'c.unique_code')
                ->groupBy([
                    'bl.entire_instance_identity_code'
                    , 'c.unique_code'
                    , 'c.name'
                ])
                ->where('c.unique_code', 'like', 'Q%')
                ->when($station_names, function ($query, $station_names) {
                    $query->whereIn('bl.maintain_station_name', $station_names);
                });

            $standby_statistics = [];
            QueryBuilderFacade::unionAll($standby_statistics_S, $standby_statistics_Q)
                ->get()
                ->each(function ($standby_statistic) use (&$standby_statistics) {
                    if (!@$standby_statistics[$standby_statistic->category_unique_code])
                        $standby_statistics[$standby_statistic->category_unique_code] = [
                            'name' => $standby_statistic->category_name
                            , 'total' => 0
                        ];
                    $standby_statistics[$standby_statistic->category_unique_code]['total'] += $standby_statistic->aggregate ?: 0;
                });
            $breakdown_statistics = [];
            QueryBuilderFacade::unionAll($breakdown_statistics_S, $breakdown_statistics_Q)
                ->get()
                ->each(function ($breakdown_statistic) use (&$breakdown_statistics) {
                    if (!@$breakdown_statistics[$breakdown_statistic->category_unique_code])
                        $breakdown_statistics[$breakdown_statistic->category_unique_code] = [
                            'name' => $breakdown_statistic->category_name
                            , 'total' => 0
                        ];
                    $breakdown_statistics[$breakdown_statistic->category_unique_code]['total'] += $breakdown_statistic->aggregate ?: 0;
                });

            return JsonResponseFacade::dict([
                'standby_statistics' => array_values($standby_statistics),
                'breakdown_statistics' => array_values($breakdown_statistics),
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取设备详情
     * @param string $identity_code
     * @return mixed
     */
    final public function getFacility(string $identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([
                'Category',
                'EntireModel',
                'EntireModel.Parent',
                'EntireModel.EntireModelImages',
                'Line',
                'SceneWorkshop',
                'Station',
                'InstallPosition',
            ])
                ->where('category_unique_code', 'like', 'S%')
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            [
                'info' => $info,
                'equipment_statistics' => $equipment_statistics,
            ] = $this->_getFacilityInfo($entire_instance);

            return JsonResponseFacade::data([
                'info' => $info,
                'equipment_statistics' => $equipment_statistics,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 获取设备器材详情
     * @param string $identity_code
     * @return mixed
     */
    final public function getEquipment(string $identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([
                'Category',
                'EntireModel',
                'EntireModel.Parent',
                'EntireModel.EntireModelImages',
                'Line',
                'SceneWorkshop',
                'Station',
                'InstallPosition',
            ])
                ->where('category_unique_code', 'like', 'Q%')
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            [
                'standbys' => $standbys,
                'current_point' => $current_point,
            ] = $this->_getStandbys($entire_instance);

            return JsonResponseFacade::data([
                'info' => $this->_getEquipmentInfo($entire_instance)
                , 'standbys' => $standbys
                , 'current_point' => $current_point
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('器材不存在');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 器材状态统计
     * @return mixed
     */
    final public function getEquipmentStatusStatistics()
    {
        try {
            $status_statistics = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(join(',', [
                    'count(ei.status) as aggregate'
                    , 'ei.status'
                ]))
                ->groupBy(['ei.status'])
                ->get();

            $statistics = [];

            $status_statistics->each(function ($status_statistic) use (&$statistics) {
                $num_status = collect(EntireInstance::$STATUSES)->get($status_statistic->status);
                if (!array_key_exists($num_status, $statistics)) $statistics[$num_status] = 0;
                $statistics[$num_status] += $status_statistic->aggregate;
            });

            return JsonResponseFacade::dict($statistics);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取现场车间器材数量统计
     */
    final public function getEquipmentCountBySceneWorkshop()
    {
        try {
            $equipment_count_statistics = EntireInstanceSerializer::INS([
                EntireInstanceSerializer::$IS_PART => false,
            ])
                ->GenerateQueryRelationShipQ()
                ->selectRaw(join(',', [
                    'count(ei.maintain_workshop_name) as aggregate'
                    , 'ei.maintain_workshop_name'
                ]))
                ->whereIn('ei.status', ['INSTALLED', 'INSTALLING'])
                ->where('ei.maintain_workshop_name', '!=', '')
                ->whereNotNUll('ei.maintain_workshop_name')
                ->groupBy(['ei.maintain_workshop_name'])
                ->get();

            return JsonResponseFacade::dict($equipment_count_statistics->pluck('aggregate', 'maintain_workshop_name'));
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取车站或设备器材的日志
     * @return mixed
     */
    final public function getLogs()
    {
        try {
            $entire_instance_logs = EntireInstanceLog::with([])
                ->selectRaw(join(',', [
                    'entire_instance_identity_code as unique_code'
                    , 'type'
                    , 'description'
                    , 'name'
                    , 'created_at'
                ]))
                ->when(
                    request('station_unique_code'),
                    function ($query, $station_unique_code) {
                        $query->where('station_unique_code', $station_unique_code);
                    }
                )
                ->when(
                    request('facility_unique_code'),
                    function ($query, $facility_unique_code) {
                        $query->where('entire_instance_identity_code', $facility_unique_code);
                    }
                )
                ->when(
                    request('equipment_unique_code'),
                    function ($query, $equipment_unique_code) {
                        $query->where('entire_instance_identity_code', $equipment_unique_code);
                    }
                )
                ->get();

            return JsonResponseFacade::dict($entire_instance_logs);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 获取器材详情
     * @param EntireInstance $entire_instance
     * @return array
     */
    final public function _getEquipmentInfo(EntireInstance $entire_instance): array
    {
        $sub_model_images = [];
        if (@$entire_instance->EntireModel->EntireModelImages) {
            $entire_instance->EntireModel->EntireModelImages->each(function ($sub_model_image) use (&$sub_model_images) {
                $sub_model_images[] = Storage::url($sub_model_image->url);
            });
        }

        $info = [];
        $info['unique_code'] = @$entire_instance->identity_code ?: '';
        $info['facility_category_name'] = '';
        $info['facility_model_name'] = '';
        $info['facility_sub_model_name'] = '';
        $info['equipment_category_name'] = @$entire_instance->Category->name ?: '';
        $info['equipment_model_name'] = @$entire_instance->EntireModel->Parent->name ?: '';
        $info['equipment_sub_model_name'] = @$entire_instance->EntireModel->name ?: '';
        $info['factory_name'] = @$entire_instance->factory_name ?: '';
        $info['service_life'] = @$entire_instance->EntireModel->life_year ?: (@$entire_instance->EntireModel->life_year ?: 0);
        $info['ex_factory_at'] = @$entire_instance->made_at ? Carbon::parse($entire_instance->made_at)->format('Y-m-d') : '';
        $info['due_date_at'] = @$entire_instance->scarping_at ? Carbon::parse($entire_instance->scarping_at)->format('Y-m-d') : '';
        $info['status_name'] = @$entire_instance->status ?: '';
        $info['line_name'] = @$entire_instance->Line->name ?: '';
        $info['workshop_name'] = @$entire_instance->SceneWorkshop->name ?: '';
        $info['station_name'] = @$entire_instance->Station->name ?: '';
        $info['install_location_name'] = @$entire_instance->InstallPosition->real_name ?: '';
        $info['serial_number'] = @$entire_instance->serial_number ?: '';
        $info['asset_code'] = @$entire_instance->asset_code ?: '';
        $info['fixed_asset_code'] = @$entire_instance->fixed_asset_code ?: '';
        $info['source_type_name'] = @$entire_instance->source_type ?: '';
        $info['source_name'] = @$entire_instance->source_name ?: '';
        $info['unit'] = '';
        $info['price'] = '';
        $info['depreciation_rate'] = '';
        $info['railway_type'] = '';
        $info['is_fixed_asset'] = '';
        $info['factory_number'] = @$entire_instance->factory_device_code ?: '';
        $info['sub_model_images'] = @$sub_model_images ?: [];
        $info['qr_code_colors'] = QrCodeFacade::generateColorsByEntireInstanceStatus($entire_instance->identity_code);

        return $info;
    }

    /**
     * 获取设备详情
     * @param EntireInstance $entire_instance
     * @return array
     */
    final public function _getFacilityInfo(EntireInstance $entire_instance): array
    {
        $info = [
            'unique_code' => @$entire_instance->identity_code ?: ''
            , 'facility_category_name' => @$entire_instance->Category->name ?: ''
            , 'facility_model_name' => @$entire_instance->EntireModel->name ?: ''
            , 'facility_sub_model_name' => ''
            , 'version_number' => ''
            , 'factory_name' => @$entire_instance->factory_name ?: ''
            , 'status_name' => @$entire_instance->status ?: ''
            , 'installed_at' => @$entire_instance->last_installed_at ? Carbon::parse(@$entire_instance->last_installed_at)->format('Y-m-d') : ''
            , 'due_date_at' => @$entire_instance->scarping_at ? Carbon::parse(@$entire_instance->scarping_at)->format('Y-m-d') : ''
            , 'line_name' => @$entire_instance->Line->name ?: ''
            , 'workshop_name' => @$entire_instance->SceneWorkshop->name ?: ''
            , 'station_name' => @$entire_instance->Station->name ?: ''
            , 'station_unique_code' => @$entire_instance->Station->unique_code ?: ''
            , 'install_location_names' => [
                $entire_instance->crossroad_number ? "道岔名称：{$entire_instance->crossroad_number}" : '',
                $entire_instance->open_direction ? "开向：{$entire_instance->open_direction}" : '',
            ]
            , 'source_type_name' => @$entire_instance->source_type ?: ''
            , 'source_name' => @$entire_instance->source_name ?: ''
            , 'ex_factory_at' => @$entire_instance->made_at ? Carbon::parse($entire_instance->made_at)->format('Y-m-d') : ''
            , 'factory_number' => @$entire_instance->factory_device_code ?: '',
        ];

        // 获取该道岔上绑定的器材
        $equipment_statistics = $entire_instance->bind_device_code
            ? DB::table('entire_instances as ei')
                ->selectRaw(join(',', [
                    'count(sm.name) as total',
                    'sm.name as sub_model_name',
                    'em.name as model_name',
                ]))
                ->join(DB::raw('entire_models sm'), 'ei.model_unique_code', '=', 'sm.unique_code')
                ->join(DB::raw('entire_models em'), 'sm.parent_unique_code', '=', 'em.unique_code')
                ->whereNull('ei.deleted_at')
                ->where('ei.status', 'INSTALLED')
                ->where('ei.bind_device_code', $entire_instance->bind_device_code)
                ->groupBy(['sm.name', 'em.name',])
                ->get()
            : [];

        $info['qr_code_colors'] = QrCodeFacade::generateColorsByEntireInstanceStatus($entire_instance->identity_code);

        return [
            'info' => $info,
            'equipment_statistics' => $equipment_statistics,
        ];
    }

    /**
     * 获取地图坐标点
     * @param EntireInstance $entire_instance
     * @return array
     */
    final public function _getStandbys(EntireInstance $entire_instance): array
    {
        // 组合数据
        $standbys = [];

        $get_statistics_db = function (string $status = null) use ($entire_instance): array {
            $db_Q = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'ei.model_unique_code as unique_code',
                    'ei.model_name as name',
                ]))
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->where('is_part', false)
                ->whereNull('ei.deleted_at')
                ->where('ei.status', '<>', 'SCRAP')
                ->where('ei.status', $status)
                ->where('ei.entire_model_unique_code', $entire_instance->entire_model_unique_code)
                ->whereNull('sm.deleted_at')
                ->where('sm.is_sub_model', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->groupBy(['ei.model_unique_code', 'ei.model_name',]);

            $db_S = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(ei.model_unique_code) as aggregate',
                    'ei.model_unique_code as unique_code',
                    'ei.model_name as name',
                ]))
                ->join(DB::raw('part_models pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
                ->join(DB::raw('part_categories pc'), 'pc.id', '=', 'pm.part_category_id')
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->where('is_part', false)
                ->whereNull('ei.deleted_at')
                ->where('ei.status', '<>', 'SCRAP')
                ->where('ei.status', $status)
                ->where('ei.entire_model_unique_code', $entire_instance->entire_model_unique_code)
                ->whereNull('pm.deleted_at')
                ->where('pc.is_main', true)
                ->whereNull('em.deleted_at')
                ->where('em.is_sub_model', false)
                ->whereNull('c.deleted_at')
                ->groupBy(['ei.model_unique_code', 'ei.model_name',]);
            return [$db_Q, $db_S];
        };

        // 当前车站备品
        list($db_Q, $db_S) = $get_statistics_db('INSTALLING');
        $db_Q->where('s.unique_code', $entire_instance->Station->unique_code);
        $db_S->where('s.unique_code', $entire_instance->Station->unique_code);
        $current_station_standby_counts = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        $current_station_standby_count = @$current_station_standby_counts->pluck('aggregate')->sum() ?: 0;
        // 组合数据
        $standbys[] = $current_station_location = [
            'name' => $entire_instance->Station->name ?: ''
            , 'type' => 'parent_station'
            , 'workshop_unique_code' => $entire_instance->Station->parent_unique_code
            , 'station_unique_code' => $entire_instance->Station->unique_code
            , 'lon' => $entire_instance->Station->lon ?: ''
            , 'lat' => $entire_instance->Station->lat ?: ''
            , 'contact' => $entire_instance->Station->contact ?: ''
            , 'contact_phone' => $entire_instance->Station->contact_phone ?: ''
            , 'count' => $current_station_standby_count
            , 'distance' => '0'
        ];

        // 临近车站备品
        $near_station_statistics = EntireInstanceSerializer::INS([
            EntireInstanceSerializer::$IS_PART => false,
        ])
            ->generateInstallingRelationShipQ($entire_instance->Station->unique_code, $entire_instance->entire_model_unique_code)
            ->get();

        foreach ($near_station_statistics as $near_station_statistic) {
            $standbys[] = [
                'name' => @$near_station_statistic->station_name ?: ''
                , 'type' => 'near_station'
                , 'workshop_unique_code' => @$near_station_statistic->workshop_unique_code ?: ''
                , 'station_unique_code' => @$near_station_statistic->station_unique_code ?: ''
                , 'lon' => @$near_station_statistic->lon ?: ''
                , 'lat' => @$near_station_statistic->lat ?: ''
                , 'contact' => @$near_station_statistic->contact ?: ''
                , 'contact_phone' => @$near_station_statistic->contact_phone ?: ''
                , 'count' => @$near_station_statistic->aggregate ?: 0
                , 'distance' => @$near_station_statistic->distance ? $near_station_statistic->distance / 1000 : '0'
            ];
        }

        // 当前现场车间
        list($db_Q, $db_S) = $get_statistics_db('INSTALLING');
        $db_Q->where('s.parent_unique_code', $entire_instance->Station->parent_unique_code);
        $db_S->where('s.parent_unique_code', $entire_instance->Station->parent_unique_code);
        $current_scene_workshop_standby_counts = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        $current_scene_workshop_standby_count = @$current_scene_workshop_standby_counts->pluck('aggregate')->sum() ?: 0;

        $scene_workshop_location = DB::table('maintains')->where('unique_code', $entire_instance->Station->parent_unique_code)->first();
        $distance_with_scene_workshop = DB::table('distance as d')
            ->selectRaw(implode(',', ['d.distance']))
            ->where('d.from_unique_code', $entire_instance->Station->unique_code)
            ->where('d.to_unique_code', $entire_instance->Station->parent_unique_code)
            ->orderBy(DB::raw('d.distance+0'))
            ->first();
        // 组合数据
        $standbys[] = [
            'name' => @$scene_workshop_location->name ?: ''
            , 'type' => 'parent_workshop'
            , 'workshop_unique_code' => @$scene_workshop_location->unique_code ?: ''
            , 'station_unique_code' => ''
            , 'lon' => @$scene_workshop_location->lon ?: ''
            , 'lat' => @$scene_workshop_location->lat ?: ''
            , 'contact' => @$scene_workshop_location->contact ?: ''
            , 'contact_phone' => @$scene_workshop_location->contact_phone ?: ''
            , 'count' => @$current_scene_workshop_standby_count
            , 'distance' => @$distance_with_scene_workshop->distance ? $distance_with_scene_workshop->distance / 1000 : '0'
        ];

        // 专业车间
        list($db_Q, $db_S) = $get_statistics_db('FIXED');
        $professional_workshop_standby_counts = ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
        $professional_workshop_standby_count = @$professional_workshop_standby_counts->pluck('aggregate')->sum() ?: 0;

        $professional_workshop_location = DB::table('maintains')->where('unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first();
        $distance_with_professional_workshop = DB::table('distance as d')
            ->selectRaw(implode(',', ['d.distance',]))
            ->where('d.from_unique_code', $entire_instance->Station->unique_code)
            ->where('d.to_unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))
            ->orderBy(DB::raw('d.distance+0'))
            ->first();
        // 组合数据
        $standbys[] = [
            'name' => @$professional_workshop_location->name ?: ''
            , 'type' => 'professional_workshop'
            , 'workshop_unique_code' => @$professional_workshop_location->unique_code ?: ''
            , 'station_unique_code' => ''
            , 'lon' => @$professional_workshop_location->lon ?: ''
            , 'lat' => @$professional_workshop_location->lat ?: ''
            , 'contact' => @$professional_workshop_location->contact ?: ''
            , 'contact_phone' => @$professional_workshop_location->contact_phone ?: ''
            , 'count' => @$professional_workshop_standby_count
            , 'distance' => @$distance_with_professional_workshop->distance ? $distance_with_professional_workshop->distance / 1000 : '0'
        ];


        $current_point = ['lon' => 0, 'lat' => 0,];
        if ($current_station_location) {
            $current_point['lon'] = $current_station_location['lon'];
            $current_point['lat'] = $current_station_location['lat'];
        } else {
            $professional_workshop_location = DB::table('maintains')->where('unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first();
            $current_point['lon'] = $professional_workshop_location->lon;
            $current_point['lat'] = $professional_workshop_location->lat;
        }

        return [
            'standbys' => $standbys
            , 'current_point' => $current_point
        ];
    }

}
