<?php

namespace App\Http\Controllers\TemporaryTask\Production;

use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLock;
use App\Exceptions\EntireInstanceLockException;
use App\Model\RepairBaseFullFixOrder;
use App\Model\RepairBaseFullFixOrderEntireInstance;
use App\Model\RepairBaseFullFixOrderModel;
use App\Model\RepairBaseHighFrequencyOrder;
use App\Model\RepairBaseHighFrequencyOrderEntireInstance;
use App\Model\RepairBaseNewStationOrder;
use App\Model\RepairBaseNewStationOrderEntireInstance;
use App\Model\RepairBaseNewStationOrderModel;
use App\Model\WarehouseReport;
use Curl\Curl;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\BadRequestException;
use Jericho\CurlHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Jericho\TextHelper;
use stdClass;

class SubController extends Controller
{

    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'temporaryTask/production/sub';
    private $_root_url = null;
    private $_auth = null;
    private $__work_areas = [];
    private $__category_with_work_area = [1 => 'S03', 2 => 'Q01'];

    final public function __construct()
    {
        $this->_spas_protocol = env('SPAS_PROTOCOL');
        $this->_spas_url_root = env('SPAS_URL_ROOT');
        $this->_spas_port = env('SPAS_PORT');
        $this->_spas_api_root = env('SPAS_API_ROOT');
        $this->_spas_username = env('SPAS_USERNAME');
        $this->_spas_password = env('SPAS_PASSWORD');
        $this->_root_url = "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = [
            "Username:{$this->_spas_username}",
            "Password:{$this->_spas_password}"
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    final public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|JsonResponse|RedirectResponse|View
     */
    final public function create()
    {
        try {
            if (!request('type')) return back()->with('danger', '没有选择子任务类型');

            $base_data = function () {
                # 获取种类和类型
                $entire_models = DB::table('entire_models as em')
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                    ->select([
                        'em.name as entire_model_name',
                        'em.unique_code as entire_model_unique_code',
                        'c.name as category_name',
                        'c.unique_code as category_unique_code',
                    ])
                    ->where('em.deleted_at', null)
                    ->where('c.deleted_at', null)
                    ->where('em.is_sub_model', false)
                    ->where('em.category_unique_code', '<>', '')
                    ->where('em.category_unique_code', '<>', null)
                    ->get();

                # 获取子类和类型
                $sub_models = DB::table('entire_models as sm')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                    ->select([
                        'sm.name as model_name',
                        'sm.unique_code as model_unique_code',
                        'em.name as entire_model_name',
                        'em.unique_code as entire_model_unique_code',
                    ])
                    ->where('sm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('sm.is_sub_model', true)
                    ->where('sm.parent_unique_code', '<>', '')
                    ->where('sm.parent_unique_code', '<>', null)
                    ->get();

                # 获取型号和类型
                $part_models = DB::table('part_models as pm')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->select([
                        'pm.name as model_name',
                        'pm.unique_code as model_unique_code',
                        'em.name as entire_model_name',
                        'em.unique_code as entire_model_unique_code',
                    ])
                    ->where('pm.deleted_at', null)
                    ->where('em.deleted_at', null)
                    ->where('pm.entire_model_unique_code', '<>', '')
                    ->where('pm.entire_model_unique_code', '<>', null)
                    ->get();

                # 组合型号和子类
                $models = $sub_models->merge($part_models);

                # 获取人员列表
                $accounts = DB::table('accounts')->pluck('nickname', 'id');

                # 获取现场车间、站场
                $stations = DB::table('maintains as s')
                    ->join(DB::raw('maintains sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                    ->select([
                        's.name as station_name',
                        's.unique_code as station_unique_code',
                        'sw.name as scene_workshop_name',
                        'sw.unique_code as scene_workshop_unique_code',
                    ])
                    ->where('s.deleted_at', null)
                    ->where('sw.deleted_at', null)
                    ->get();

                return [$entire_models, $models, $accounts, $stations];
            };
            list($entire_models, $models, $accounts, $stations) = $base_data();

            $new_station = function () use ($entire_models, $models, $accounts, $stations) {
                # 新站（先入后出）
                # 获取主任务详情
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # 获取该主任务下所有新站未分配型号
                $new_station_models = RepairBaseNewStationOrderModel::with(['Order'])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->whereHas('Order', function ($Order) {
                        return $Order->where('direction', 'IN');
                    })
                    ->get();

                return view('TemporaryTask.Production.Sub.create_NEW_STATION', [
                    'main_task' => $main_task_response['body']['data'],
                    'accounts' => $accounts,
                    'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                    'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                    'entire_models_as_json' => $entire_models->groupBy('category_unique_code')->tojson(),
                    'categories' => $entire_models->pluck('category_name', 'category_unique_code')->all(),
                    'models_as_json' => $models->groupBy('entire_model_unique_code')->toJson(),
                    'new_station_models' => $new_station_models,
                ]);
            };

            $full_fix = function () use ($entire_models, $models, $accounts, $stations) {
                # 大修（先入后出）
                # 获取主任务详情
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # 获取该主任务下所有新站未分配型号
                $full_fix_models = RepairBaseFullFixOrderModel::with(['Order'])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get();

                return view('TemporaryTask.Production.Sub.create_FULL_FIX', [
                    'main_task' => $main_task_response['body']['data'],
                    'accounts' => $accounts,
                    'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                    'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                    'scene_workshops_as_json' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->toJson(),
                    'entire_models_as_json' => $entire_models->groupBy('category_unique_code')->tojson(),
                    'categories' => $entire_models->pluck('category_name', 'category_unique_code')->all(),
                    'models_as_json' => $models->groupBy('entire_model_unique_code')->toJson(),
                    'full_fix_models' => $full_fix_models,
                ]);
            };

            $high_frequency = function () use ($entire_models, $models, $accounts, $stations) {
                # 大修（先出后入）
                # 获取主任务详情
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                return view('TemporaryTask.Production.Sub.create_HIGH_FREQUENCY', [
                    'main_task' => $main_task_response['body']['data'],
                    'accounts' => $accounts,
                    'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                    'scene_workshops' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->all(),
                    'scene_workshops_as_json' => $stations->pluck('scene_workshop_name', 'scene_workshop_unique_code')->toJson(),
                    'entire_models_as_json' => $entire_models->groupBy('category_unique_code')->tojson(),
                    'categories' => $entire_models->pluck('category_name', 'category_unique_code')->all(),
                    'models_as_json' => $models->groupBy('entire_model_unique_code')->toJson(),
                ]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (BadRequestException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据中台链接失败');
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                # 获取主任务详情
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # 读取未分配的型号
                $new_station_models_in = RepairBaseNewStationOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();
                if (empty($new_station_models_in)) return response()->json(['message' => '所选型号为空'], 404);

                # 获取各个工区负责人
                $work_area_accounts = DB::table('accounts')
                    ->where('deleted_at', null)
                    ->where('temp_task_position', 'ParagraphWorkArea')
                    ->groupBy('work_area')
                    ->get();
                if ($work_area_accounts->isEmpty()) return response()->json(['message' => '未设置工区负责人'], 404);
                if (!$work_area_accounts->get(1)) return response()->json(['message' => '未设置转辙机工区负责人'], 404);
                if (!$work_area_accounts->get(2)) return response()->json(['message' => '未设置继电器工区负责人'], 404);
                if (!$work_area_accounts->get(3)) return response()->json(['message' => '未设置综合工区负责人'], 404);
                $work_area_names = [1 => '转辙机工区', 2 => '继电器工区', 3 => '综合工区'];

                $now = date('Y-m-d H:i:s');  # 当前时间

                $ret = [];

                foreach ($new_station_models_in as $work_area_id => $new_station_model_in) {
                    $ret[] = DB::transaction(function () use ($now, $main_task_response, $work_area_accounts, $work_area_id, $work_area_names, $request, $new_station_model_in) {
                        $ret = [];

                        # 创建新站入所任务单
                        DB::table('repair_base_new_station_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $new_station_order_in_sn = CodeFacade::makeSerialNumber('NEW_STATION_IN'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'IN',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId'),
                        ]);

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '新站入所任务',
                                'intro' => '新站入所任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$new_station_order_in_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $new_station_order_in_sn,
                                'type' => 'NEW_STATION',
                                'direction' => 'IN',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # 更新型号入所任务单号
                        DB::table('repair_base_new_station_order_models')
                            ->whereIn('id', collect($new_station_model_in)->pluck('id')->toArray())
                            ->update([
                                'new_station_model_order_sn' => $new_station_order_in_sn
                            ]);

                        # 创建新站出所任务单
                        DB::table('repair_base_new_station_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $new_station_order_out_sn = CodeFacade::makeSerialNumber('NEW_STATION_OUT'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'OUT',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId'),
                            'in_sn' => $new_station_order_in_sn,
                        ]);

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '新站出所任务',
                                'intro' => '新站出所任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$new_station_order_out_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $new_station_order_out_sn,
                                'type' => 'NEW_STATION',
                                'direction' => 'OUT',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # 复制入所型号到出所型号
                        $new_station_models_out = [];
                        foreach ($new_station_model_in as $item) {
                            $new_station_models_out[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'model_name' => $item->model_name,
                                'model_unique_code' => $item->model_unique_code,
                                'entire_model_name' => $item->entire_model_name,
                                'entire_model_unique_code' => $item->entire_model_unique_code,
                                'category_name' => $item->category_name,
                                'category_unique_code' => $item->category_unique_code,
                                'work_area_id' => $item->work_area_id,
                                'number' => $item->number,
                                'picked' => $item->picked,
                                'new_station_model_order_sn' => $new_station_order_out_sn,
                                'temporary_task_production_main_id' => $item->temporary_task_production_main_id,
                                'temporary_task_production_sub_id' => $item->temporary_task_production_sub_id,
                            ];
                        }
                        DB::table('repair_base_new_station_order_models')->insert($new_station_models_out);

                        return $ret;
                    });
                }

                # 获取所有该主任务下所有型号
                $new_station_orders_as_json = RepairBaseNewStationOrderModel::with([
                    'SubModel',
                    'PartModel',
                    'EntireModel',
                    'Category',
                ])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->toJson(256);

                $save_main_task_file_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/saveMainTaskFile/" . request('mainTaskId'),
                    'method' => CurlHelper::POST,
                    'contents' => [
                        'orders' => $new_station_orders_as_json
                    ],
                ]);
                if ($save_main_task_file_response['code'] > 399) return response()->json($save_main_task_file_response['body'], $save_main_task_file_response['code']);

                return response()->json(['message' => '新建成功', 'ret' => $ret, 'save_main_file' => $save_main_task_file_response]);
            };

            $full_fix = function () use ($request) {
                # 获取主任务详情
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/" . request('mainTaskId'),
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                # 入所任务型号（被选中的型号）
                $full_fix_models_in = RepairBaseFullFixOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->where('picked', true)
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();
                if (empty($full_fix_models_in)) return response()->json(['message' => '没有选中任何型号'], 404);

                # 出所任务型号（全部型号）
                $full_fix_models_out = RepairBaseFullFixOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();

                # 报废型号（未选中的型号）
                $full_fix_models_scrap = RepairBaseFullFixOrderModel::with([])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->where('picked', false)
                    ->get()
                    ->groupBy('work_area_id')
                    ->all();

                # 获取各个工区负责人
                $work_area_accounts = DB::table('accounts')->where('deleted_at', null)->where('temp_task_position', 'ParagraphWorkArea')->groupBy('work_area')->get();
                if ($work_area_accounts->isEmpty()) return response()->json(['message' => '未设置工区负责人'], 404);
                if (!$work_area_accounts->get(1)) return response()->json(['message' => '未设置转辙机工区负责人'], 404);
                if (!$work_area_accounts->get(2)) return response()->json(['message' => '未设置继电器工区负责人'], 404);
                if (!$work_area_accounts->get(3)) return response()->json(['message' => '未设置综合工区负责人'], 404);
                $work_area_names = [1 => '转辙机工区', 2 => '继电器工区', 3 => '综合工区'];

                $now = date('Y-m-d H:i:s');  # 当前时间

                $ret = [];

                # 大修入所任务
                foreach ($full_fix_models_in as $work_area_id => $full_fix_model_in) {
                    $ret[] = DB::transaction(function () use (
                        $now,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_id,
                        $work_area_names,
                        $request,
                        $full_fix_model_in
                    ) {
                        $ret = [];

                        # 创建大修入所任务单
                        DB::table('repair_base_full_fix_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $full_fix_order_in_sn = CodeFacade::makeSerialNumber('FULL_FIX_IN'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'IN',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId')
                        ]);

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '大修入所任务',
                                'intro' => '大修入所任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$full_fix_order_in_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $full_fix_order_in_sn,
                                'type' => 'FULL_FIX',
                                'direction' => 'IN',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # 相关型号入所任务编号
                        DB::table('repair_base_full_fix_order_models')
                            ->whereIn('id', collect($full_fix_model_in)->pluck('id')->all())
                            ->update(['full_fix_order_sn' => $full_fix_order_in_sn, 'direction' => 'IN',]);

                        return $ret;
                    });
                }

                # 大修出所任务
                foreach ($full_fix_models_out as $work_area_id => $full_fix_model_out) {
                    $ret[] = DB::transaction(function () use (
                        $now,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_id,
                        $work_area_names,
                        $request,
                        $full_fix_model_out
                    ) {
                        $ret = [];

                        # 创建大修入所任务单
                        DB::table('repair_base_full_fix_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $full_fix_order_out_sn = CodeFacade::makeSerialNumber('FULL_FIX_OUT'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'OUT',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId')
                        ]);

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '大修出所任务',
                                'intro' => '大修出所任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$full_fix_order_out_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $full_fix_order_out_sn,
                                'type' => 'FULL_FIX',
                                'direction' => 'OUT',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        $insert = [];
                        foreach ($full_fix_model_out as $item) {
                            $insert[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'model_name' => $item->model_name,
                                'model_unique_code' => $item->model_unique_code,
                                'entire_model_name' => $item->entire_model_name,
                                'entire_model_unique_code' => $item->entire_model_unique_code,
                                'category_name' => $item->category_name,
                                'category_unique_code' => $item->category_unique_code,
                                'work_area_id' => $work_area_id,
                                'number' => $item->number,
                                'full_fix_order_sn' => $full_fix_order_out_sn,
                                'picked' => $item->picked,
                                'temporary_task_production_main_id' => $item->temporary_task_production_main_id,
                                'temporary_task_production_sub_id' => $item->temporary_task_production_sub_id,
                                'direction' => 'OUT',
                            ];
                        }
                        if (!empty($insert)) DB::table('repair_base_full_fix_order_models')->insert($insert);

                        return $ret;
                    });
                }

                # 大修报废任务
                foreach ($full_fix_models_scrap as $work_area_id => $full_fix_model_scrap) {
                    $ret[] = DB::transaction(function () use (
                        $now,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_id,
                        $work_area_names,
                        $request,
                        $full_fix_model_scrap
                    ) {
                        $ret = [];

                        # 创建大修报废任务单
                        DB::table('repair_base_full_fix_orders')->insert([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'serial_number' => $full_fix_order_scrap_sn = CodeFacade::makeSerialNumber('FULL_FIX_SCRAP'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'SCRAP',
                            'work_area_id' => $work_area_id,
                            'temporary_task_production_main_id' => request('mainTaskId')
                        ]);

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '大修报废任务',
                                'intro' => '大修报废任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$full_fix_order_scrap_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $full_fix_order_scrap_sn,
                                'type' => 'FULL_FIX',
                                'direction' => 'SCRAP',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # 复制型号给出所任务单使用
                        $insert = [];
                        foreach ($full_fix_model_scrap as $item) {
                            $insert[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'model_name' => $item->model_name,
                                'model_unique_code' => $item->model_unique_code,
                                'entire_model_name' => $item->entire_model_name,
                                'entire_model_unique_code' => $item->entire_model_unique_code,
                                'category_name' => $item->category_name,
                                'category_unique_code' => $item->category_unique_code,
                                'work_area_id' => $work_area_id,
                                'number' => $item->number,
                                'full_fix_order_sn' => $full_fix_order_scrap_sn,
                                'picked' => $item->picked,
                                'temporary_task_production_main_id' => $item->temporary_task_production_main_id,
                                'temporary_task_production_sub_id' => $item->temporary_task_production_sub_id,
                                'direction' => 'SCRAP',
                            ];
                        }
                        if (!empty($insert)) DB::table('repair_base_full_fix_order_models')->insert($insert);

                        return $ret;
                    });
                }

                # 获取所有该主任务下所有型号
                $full_fix_orders_as_json = RepairBaseFullFixOrderModel::with([
                    'SubModel',
                    'PartModel',
                    'EntireModel',
                    'Category',
                ])
                    ->where('temporary_task_production_main_id', request('mainTaskId'))
                    ->get()
                    ->groupBy('work_area_id')
                    ->toJson(256);

                $save_main_task_file_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/saveMainTaskFile/" . request('mainTaskId'),
                    'method' => CurlHelper::POST,
                    'contents' => [
                        'orders' => $full_fix_orders_as_json
                    ],
                ]);
                if ($save_main_task_file_response['code'] > 399) return response()->json($save_main_task_file_response['body'], $save_main_task_file_response['code']);

                return response()->json(['message' => '新建成功', 'ret' => $ret, 'save_main_file' => $save_main_task_file_response]);
            };

            $high_frequency = function () use ($request) {
                $ret = [];
                return DB::transaction(function () use ($request, &$ret) {
                    # 获取所有设备，按照工区区分
                    $work_area_1 = EntireInstance::with(['Station', 'Station.Parent'])->whereIn('identity_code', $request->get('entireInstances'))->where('category_unique_code', 'S03')->get();
                    $work_area_2 = EntireInstance::with(['Station', 'Station.Parent'])->whereIn('identity_code', $request->get('entireInstances'))->where('category_unique_code', 'Q01')->get();
                    $work_area_3 = EntireInstance::with(['Station', 'Station.Parent'])->whereIn('identity_code', $request->get('entireInstances'))->whereNotIn('category_unique_code', ['S03', 'Q01'])->get();

                    # 获取主任务详情
                    $main_task_response = CurlHelper::init([
                        'headers' => $this->_auth,
                        'url' => "{$this->_root_url}/temporaryTask/production/main/{$request->get('mainTaskId')}",
                        'method' => CurlHelper::GET,
                    ]);
                    if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

                    # 获取各个工区负责人
                    $work_area_accounts = DB::table('accounts')->where('deleted_at', null)->where('temp_task_position', 'ParagraphWorkArea')->groupBy('work_area')->get();
                    if ($work_area_accounts->isEmpty()) return response()->json(['message' => '未设置工区负责人'], 404);
                    if (!$work_area_accounts->get(1)) return response()->json(['message' => '未设置转辙机工区负责人'], 404);
                    if (!$work_area_accounts->get(2)) return response()->json(['message' => '未设置继电器工区负责人'], 404);
                    if (!$work_area_accounts->get(3)) return response()->json(['message' => '未设置综合工区负责人'], 404);
                    $work_area_names = [1 => '转辙机工区', 2 => '继电器工区', 3 => '综合工区'];

                    $_make = function (
                        array $entire_instances,
                        int $work_area_id
                    )
                    use (
                        $request,
                        $main_task_response,
                        $work_area_accounts,
                        $work_area_names,
                        &$ret
                    ) {
                        # 创建高频修入所任务
                        $high_frequency_order_in = new RepairBaseHighFrequencyOrder();
                        $high_frequency_order_in->fill([
                            'serial_number' => $high_frequency_order_in_sn = CodeFacade::makeSerialNumber('HIGH_FREQUENCY_IN'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'IN',
                            'work_area_id' => $work_area_id,
                        ])->saveOrFail();

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '高频修入所任务',
                                'intro' => '高频修入所任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$high_frequency_order_in_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $high_frequency_order_in_sn,
                                'type' => $request->get('type'),
                                'direction' => 'IN',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # 创建高频修出所任务
                        $high_frequency_order_out = new RepairBaseHighFrequencyOrder();
                        $high_frequency_order_out->fill([
                            'serial_number' => $high_frequency_order_out_sn = CodeFacade::makeSerialNumber('HIGH_FREQUENCY_OUT'),
                            'scene_workshop_code' => $request->get('sceneWorkshopCode'),
                            'station_code' => $request->get('stationCode'),
                            'direction' => 'OUT',
                            'work_area_id' => $work_area_id,
                            'in_sn' => $high_frequency_order_in_sn,
                        ])->saveOrFail();

                        # 创建数据中台子任务和消息
                        $response = CurlHelper::init([
                            'headers' => $this->_auth,
                            'url' => "{$this->_root_url}/{$this->_spas_url}",
                            'method' => CurlHelper::POST,
                            'contents' => [
                                'title' => $main_task_response['body']['data']['title'],
                                'content' => '高频修出所任务',
                                'intro' => '高频修出所任务',
                                'initiator_id' => session('account.id'),
                                'initiator_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_id' => $work_area_accounts->get($work_area_id)->id,
                                'receiver_affiliation' => env('ORGANIZATION_CODE'),
                                'receiver_work_area_id' => $work_area_id,
                                'receiver_work_area_name' => $work_area_names[$work_area_id],
                                'main_task_id' => $main_task_response['body']['data']['id'],
                                'subjoin' => json_encode([
                                    'before' => [
                                        'operator' => 'href',
                                        'uri' => "/temporaryTask/production/sub/{$high_frequency_order_out_sn}?type={$request->get('type')}",
                                    ],
                                    'after' => []
                                ]),
                                'workshop_serial_number' => $high_frequency_order_out_sn,
                                'type' => $request->get('type'),
                                'direction' => 'OUT',
                            ],
                        ]);
                        if ($response['code'] > 399) return response()->json($response['body'], $response['code']);
                        $ret[] = $response;

                        # 创建出入所设备
                        $high_frequency_order_entire_instances = [];
                        $entire_instance_locks = [];
                        foreach ($entire_instances as $entire_instance) {
                            $high_frequency_order_entire_instances[] = [
                                'old_entire_instance_identity_code' => $entire_instance->identity_code,
                                'maintain_location_code' => $entire_instance->maintain_location_code,
                                'crossroad_number' => $entire_instance->crossroad_number,
                                'in_sn' => $high_frequency_order_in_sn,
                                'out_sn' => $high_frequency_order_out_sn,
                                'source' => $entire_instance->source,
                                'source_traction' => $entire_instance->source_traction,
                                'source_crossroad_number' => $entire_instance->source_crossroad_number,
                                'traction' => $entire_instance->traction,
                                'open_direction' => $entire_instance->open_direction,
                                'said_rod' => $entire_instance->said_rod,
                                'scene_workshop_name' => @$entire_instance->Station->Parent->name,
                                'station_name' => @$entire_instance->Station->name,
                            ];
                            $entire_instance_locks[$entire_instance->identity_code] = "设备器材：{$entire_instance->identity_code}，在高频修入所中被使用。详情，高频修任务：{$high_frequency_order_in_sn}";
                        }
                        # 入所设备上锁
                        EntireInstanceLock::setOnlyLocks(
                            array_pluck($high_frequency_order_entire_instances, 'identity_code'),
                            ['HIGH_FREQUENCY'],
                            $entire_instance_locks,
                            function () use ($high_frequency_order_entire_instances) {
                                DB::table('repair_base_high_frequency_order_entire_instances')->insert($high_frequency_order_entire_instances);
                            });

                        return null;
                    };

                    # 转辙机工区
                    if ($work_area_1->isNotEmpty()) $_make($work_area_1->all(), 1);
                    # 继电器工区
                    if ($work_area_2->isNotEmpty()) $_make($work_area_2->all(), 2);
                    # 综合工区
                    if ($work_area_3->isNotEmpty()) $_make($work_area_3->all(), 3);

                    return response()->json(['message' => '创建成功', 'main_task_id' => $request->get('mainTaskId')]);
                });

            };

            $func = strtolower($request->get('type'));
            return $$func();
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据为空', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 404);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $sn
     * @return Factory|Application|RedirectResponse|View
     */
    final public function show($sn)
    {
        try {
            $new_station = function () use ($sn) {
                # 获取子任务信息
                $sub_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/byWorkshopSerialNumber/{$sn}",
                    'method' => CurlHelper::GET,
                    'queries' => [
                        'page' => '',
                        'message_id' => request('message_id'),
                    ],
                ]);
                if ($sub_task_response['code'] > 399) return response()->json($sub_task_response['body'], $sub_task_response['code']);

                # 获取主任务信息
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

                # 新站任务单
                $new_station_order = RepairBaseNewStationOrder::with([
                    'Models',
                    'InEntireInstances' => function ($InEntireInstances) {
                        return $InEntireInstances->orderBy('in_warehouse_sn')->orderBy('id', 'desc');
                    },
                    'InEntireInstances.OldEntireInstance',
                    'OutEntireInstances' => function ($OutEntireInstances) {
                        return $OutEntireInstances->orderBy('out_warehouse_sn')->orderBy('id', 'desc');
                    },
                    'OutEntireInstances.OldEntireInstance',
                ])
                    ->where('serial_number', $sn)
                    ->firstOrFail();

                # 统计已经出入所设备
                switch ($new_station_order->direction) {
                    default:
                    case 'IN':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_new_station_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.in_sn = ?
  and r.in_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                    case 'OUT':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_new_station_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.out_sn = ?
  and r.out_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                }

                return view('TemporaryTask.Production.Sub.show_NEW_STATION', [
                    'main_task' => $main_task_response['body']['data'],
                    'sub_task' => $sub_task_response['body']['data'],
                    'new_station_order' => $new_station_order,
                    'warehouse_aggregate' => $warehouse_aggregate,
                ]);
            };

            $full_fix = function () use ($sn) {
                # 获取子任务信息
                $sub_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/byWorkshopSerialNumber/{$sn}",
                    'method' => CurlHelper::GET,
                    'queries' => [
                        'page' => '',
                        'message_id' => request('message_id'),
                    ],
                ]);
                if ($sub_task_response['code'] > 399) return response()->json($sub_task_response['body'], $sub_task_response['code']);

                # 获取主任务信息
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

                $full_fix_order = RepairBaseFullFixOrder::with(['Models'])->where('serial_number', $sn)->firstOrFail();

                # 统计已经出入所设备
                switch ($full_fix_order->direction) {
                    default:
                    case 'IN':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_full_fix_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.in_sn = ?
  and r.in_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                    case 'OUT':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_full_fix_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.out_sn = ?
  and r.out_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                    case 'SCRAP':
                        $warehouse_aggregate = collect(DB::select("select count(ei.model_name) as aggregate , ei.model_name from repair_base_full_fix_order_entire_instances as r
join entire_instances ei on ei.identity_code = r.old_entire_instance_identity_code
where r.scrap_sn = ?
  and r.scrap_warehouse_sn <> ''
group by ei.model_name", [$sn]))->pluck('aggregate', 'model_name');
                        break;
                }

                return view('TemporaryTask.Production.Sub.show_FULL_FIX', [
                    'main_task' => $main_task_response['body']['data'],
                    'sub_task' => $sub_task_response['body']['data'],
                    'full_fix_order' => $full_fix_order,
                ]);
            };

            $high_frequency = function () use ($sn) {
                # 获取子任务信息
                $sub_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/byWorkshopSerialNumber/{$sn}",
                    'method' => CurlHelper::GET,
                    'queries' => [
                        'page' => '',
                        'message_id' => request('message_id'),
                    ],
                ]);
                if ($sub_task_response['code'] > 399) return response()->json($sub_task_response['body'], $sub_task_response['code']);

                # 获取主任务信息
                $main_task_response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                    'method' => CurlHelper::GET,
                ]);
                if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

                $high_frequency_order = RepairBaseHighFrequencyOrder::with([])->where('serial_number', $sn)->firstOrFail();

                return view('TemporaryTask.Production.Sub.show_HIGH_FREQUENCY', [
                    'main_task' => $main_task_response['body']['data'],
                    'sub_task' => $sub_task_response['body']['data'],
                    'high_frequency_order' => $high_frequency_order,
                ]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (BadRequestException $e) {
            return back()->with('danger', '数据中台链接失败');
        } catch (Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    final public function destroy($id)
    {
        //
    }

    /**
     * 查看改型号下的设备
     * @return JsonResponse
     */
    final public function getModel()
    {
        try {
            $full_fix = function () {
                return response()->json([
                    'message' => '读取成功',
                    'entire_instances' => DB::table('repair_base_full_fix_order_entire_instances')
                        ->where('model_unique_code', request('modelUniqueCode'))
                        ->where('temporary_task_production_main_id', request('mainTaskId'))
                        ->get()
                ]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (\Throwable $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]]);
        }
    }

    /**
     * 添加型号数量
     * @param Request $request
     * @return JsonResponse
     */
    final public function postModel(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                switch (substr($request->get('modelUniqueCode'), 0, 1)) {
                    case 'S':
                        $model = DB::table('part_models as m')
                            ->select([
                                'm.id as id',
                                'm.name as model_name',
                                'm.unique_code as model_unique_code',
                                'em.name as entire_model_name',
                                'em.unique_code as entire_model_unique_code',
                                'c.name as category_name',
                                'c.unique_code as category_unique_code',
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code');
                        break;
                    case 'Q':
                        $model = DB::table('entire_models as m')
                            ->select([
                                'm.id as id',
                                'm.name as model_name',
                                'm.unique_code as model_unique_code',
                                'em.name as entire_model_name',
                                'em.unique_code as entire_model_unique_code',
                                'c.name as category_name',
                                'c.unique_code as category_unique_code',
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code');
                        break;
                    default:
                        return response()->json(['message' => '型号代码错误']);
                        break;
                }

                $model = $model->where('m.unique_code', $request->get('modelUniqueCode'))->first();
                if (!$model) return response()->json(['message' => '型号不存在'], 403);

                switch (substr($request->get('modelUniqueCode'), 0, 3)) {
                    case 'S03':
                        $work_area_id = 1;
                        break;
                    case 'Q01':
                        $work_area_id = 2;
                        break;
                    default:
                        $work_area_id = 3;
                        break;
                }

                $repeat = RepairBaseNewStationOrderModel::with([])
                    ->where('model_unique_code', $request->get('modelUniqueCode'))
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->first();
                if ($repeat) return response()->json(['message' => '重复创建'], 403);

                $new_station_order_model = new RepairBaseNewStationOrderModel([
                    'model_name' => $model->model_name,
                    'model_unique_code' => $model->model_unique_code,
                    'entire_model_name' => $model->entire_model_name,
                    'entire_model_unique_code' => $model->entire_model_unique_code,
                    'category_name' => $model->category_name,
                    'category_unique_code' => $model->category_unique_code,
                    'work_area_id' => $work_area_id,
                    'number' => $request->get('number'),
                    'temporary_task_production_main_id' => $request->get('mainTaskId'),
                ]);
                $new_station_order_model->saveOrFail();

                return response()->json(['message' => '添加成功', 'data' => $new_station_order_model]);
            };

            $full_fix = function () use ($request) {
                $full_fix_model = RepairBaseFullFixOrderModel::with([])
                    ->where('id', $request->get('id'))
                    ->firstOrFail();
                $full_fix_model->fill(['picked' => true])->saveOrFail();

                # 获取该型号下所有设备
                $entire_instances = RepairBaseFullFixOrderEntireInstance::with([])
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->where('model_unique_code', $request->get('modalUniqueCode'))
                    ->get();

                return response()->json([
                    'message' => '绑定成功',
                    'entire_instances' => $entire_instances,
                    'requests' => $request->all(),
                ]);
            };

            $fun_name = strtolower($request->get('type'));
            return $$fun_name();
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => '型号不存在', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 403);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 删除型号
     * @param Request $request
     * @return JsonResponse
     */
    final public function deleteModel(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                $new_station_model = RepairBaseNewStationOrderModel::with([])
                    ->where('id', $request->get('id'))
                    ->firstOrFail();

                $new_station_model->delete();

                return response()->json(['message' => '删除成功']);
            };

            $full_fix = function () use ($request) {
                DB::table('repair_base_full_fix_order_entire_instances')
                    ->where('model_unique_code', $request->get('modelUniqueCode'))
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->delete();
                DB::table('repair_base_full_fix_order_models')
                    ->where('id', $request->get('id'))
                    ->update(['picked' => false]);
                return response()->json(['message' => '删除成功']);
            };

            $fun_name = strtolower($request->get('type'));
            return $$fun_name();
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => '型号不存在', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 403);
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 获取设备
     */
    final public function getEntireInstance()
    {
        try {
            $high_frequency = function () {
                $entire_instances = EntireInstance::with([])
                    ->when(request('searchType'), function ($query) {
                        switch (request('searchType')) {
                            default:
                            case 'CODE':
                                return $query->where('identity_code', request('searchCondition'))->whereOr('serial_number', request('searchCondition'));
                                break;
                            case 'LOCATION':
                                return $query->where('maintain_location_code', request('searchCondition'))->whereOr('crossroad_number', request('searchCondition'));
                                break;
                        }
                    })
                    ->whereHas('Station', function ($Station) {
                        return $Station->where('unique_code', request('stationCode'));
                    })
                    ->whereIn('status', ['INSTALLED', 'INSTALLING', 'TRANSFER_IN', 'TRANSFER_OUT'])
                    ->get();
                return response()->json(['message' => '获取成功', 'entire_instances' => $entire_instances]);
            };

            $func = strtolower(request('type'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '']);
        } catch (\Throwable $e) {
            return response()->json(['message' => '意外错误'], 500);
        }
    }

    /**
     * 添加设备
     * @param Request $request
     * @return JsonResponse
     */
    final public function postEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $new_station = function () use ($request) {
                    /**
                     * 入所扫码
                     * @return JsonResponse
                     */
                    $in = function () use ($request) {
                        $new_station_order_out = RepairBaseNewStationOrder::with([])->where('in_sn', $request->get('serialNumber'))->first();
                        if (!$new_station_order_out) return response()->json(['message' => '新站出所任务没有找到'], 404);
                        $new_station_order_model_unique_codes = RepairBaseNewStationOrderModel::with([])
                            ->where('temporary_task_production_main_id', $new_station_order_out->temporary_task_production_main_id)
                            ->groupBy('model_unique_code')
                            ->get()
                            ->pluck('model_unique_code')
                            ->all();
                        $entire_instance = EntireInstance::with(['Station', 'Station.Parent'])
                            ->where('identity_code', $request->get('identityCode'))
                            ->whereIn('model_unique_code', $new_station_order_model_unique_codes)
                            ->first();
                        if (!$entire_instance) return response()->json(['message' => '设备不存在'], 404);

                        try {
                            $lock_ret = EntireInstanceLock::setOnlyLock(
                                $request->get('identityCode'),
                                ['NEW_STATION'],
                                "设备器材：{$request->get('identityCode')}，在新站入所任务中被使用。详情：新站入所任务{$request->get('serialNumber')}",
                                function () use ($request, $new_station_order_out, $entire_instance) {
                                    # 记录新站入所设备
                                    $new_station_entire_instance = new RepairBaseNewStationOrderEntireInstance();
                                    $new_station_entire_instance->fill([
                                        'old_entire_instance_identity_code' => $request->get('identityCode'),
                                        'maintain_location_code' => $entire_instance->maintain_location_code,
                                        'crossroad_number' => $entire_instance->crossroad_number,
                                        'source' => $entire_instance->source,
                                        'source_crossroad_number' => $entire_instance->source_crossroad_number,
                                        'source_traction' => $entire_instance->source_traction,
                                        'traction' => $entire_instance->traction,
                                        'open_direction' => $entire_instance->open_direction,
                                        'said_rod' => $entire_instance->said_rod,
                                        'in_sn' => $request->get('serialNumber'),
                                        'out_sn' => $new_station_order_out->serial_number,
                                    ])->saveOrFail();
                                });

                            return response()->json(['message' => '添加成功', 'lock_ret' => $lock_ret]);
                        } catch (EntireInstanceLockException $e) {
                            return response()->json(['message' => $e->getMessage()], 403);
                        }
                    };

                    /**
                     * 出所扫码
                     * @return JsonResponse
                     */
                    $out = function () use ($request) {
                        $new_station_order_out = RepairBaseNewStationOrder::with([])->where('serial_number', $request->get('serialNumber'))->first();
                        if (!$new_station_order_out) return response()->json(['message' => '新站出所任务没有找到'], 404);

                        # 添加新站出所扫码标记
                        $new_station_entire_instance = RepairBaseNewStationOrderEntireInstance::with([])
                            ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                            ->where('out_sn', $request->get('serialNumber'))
                            ->first();
                        if (!$new_station_entire_instance) return response()->json(['message' => '设备不存在'], 404);
                        $new_station_entire_instance->fill(['out_scan' => true])->saveOrFail();

                        return response()->json(['message' => '扫码成功']);
                    };

                    $func = strtolower($request->get('direction'));
                    return $$func();
                };

                $full_fix = function () use ($request) {
                    DB::table('repair_base_full_fix_order_entire_instances')
                        ->where('id', $request->get('id'))
                        ->update(['picked' => true]);
                    return response()->json(['message' => '添加成功']);
                };

                $high_frequency = function () use ($request) {
                    $in = function () use ($request) {
                        $high_frequency_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([])
                            ->where('old_entire_instance_identity_code', $request->get('identityCode'))
                            ->first();
                        if (!$high_frequency_entire_instance) return response()->json(['message' => '设备不存在'], 404);
                        $high_frequency_entire_instance->fill(['in_scan' => true])->saveOrFail();  # 添加入所扫码标记

                        return response()->json(['message' => '扫码成功']);
                    };

                    $out = function () use ($request) {
                        $high_frequency_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([])
                            ->where('new_entire_instance_identity_code', $request->get('identityCode'))
                            ->first();
                        if (!$high_frequency_entire_instance) return response()->json(['message' => '设备不存在'], 404);
                        $high_frequency_entire_instance->fill(['out_scan' => true])->saveOrFail();  # 添加出所扫码表姐

                        return response()->json(['message' => '扫码成功']);
                    };

                    $func = strtolower($request->get('direction'));
                    return $$func();
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (EntireInstanceLockException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 删除设备
     * @param Request $request
     * @return JsonResponse
     */
    final public function deleteEntireInstance(Request $request)
    {
        try {
            $new_station = function () use ($request) {
                $new_station_entire_instance = RepairBaseNewStationOrderEntireInstance::with([])->where('id', $request->get('id'))->first();
                if (!$new_station_entire_instance) return response()->json(['message' => '设备不存在'], 404);

                EntireInstanceLock::freeLock(
                    $new_station_entire_instance->old_entire_instance_identity_code,
                    ['NEW_STATION'],
                    function () use ($new_station_entire_instance) {
                        $new_station_entire_instance->delete();
                    });

                return response()->json(['message' => '删除成功']);
            };

            $full_fix = function () use ($request) {
                DB::table('repair_base_full_fix_order_entire_instances')
                    ->where('id', $request->get('id'))
                    ->update(['picked' => false]);
                return response()->json(['message' => '删除']);
            };

            $high_frequency = function () use ($request) {
                $high_frequency = RepairBaseHighFrequencyOrderEntireInstance::with([])->where('id', $request->get('id'))->first();
                if (!$high_frequency) return response()->json(['message' => '设备不存在'], 404);
                $direction = strtolower($request->get('direction')) . '_scan';
                $high_frequency->fill([$direction => false])->saveOrFail();

                return response()->json(['message' => '清除成功']);
            };

            $func = strtolower($request->get('type'));
            return $$func();
        } catch (\Throwable $th) {
            return response()->json(['message' => '异常错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 出入所
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWarehouse(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $new_station = function () use ($request) {
                    $work_area_id = array_flip(Account::$WORK_AREAS)[session('account.work_area')];
                    $now = date('Y-m-d H:i:s');
                    # 获取新站任务单
                    $new_station_order = RepairBaseNewStationOrder::with(['SceneWorkshop', 'Station'])->where('serial_number', $request->get('serialNumber'))->first();
                    if (!$new_station_order) return response()->json(['message' => '新站任务不存在'], 404);
                    $func = strtolower(array_flip(RepairBaseNewStationOrder::$DIRECTIONS)[$new_station_order->direction]);

                    # 入所
                    $in = function () use ($request, $work_area_id, $now) {
                        # 读取未入所设备
                        $new_station_entire_instances = RepairBaseNewStationOrderEntireInstance::with([])
                            ->where('in_warehouse_sn', '')
                            ->where('in_sn', $request->get('serialNumber'))
                            ->get();
                        if ($new_station_entire_instances->isEmpty()) return response()->json(['message' => '没有添加任何设备'], 404);
                        $new_station_entire_instance_identity_codes = $new_station_entire_instances->pluck('old_entire_instance_identity_code')->toArray();

                        # 设备入所
                        $in_warehouse_sn = WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                            $new_station_entire_instance_identity_codes,
                            session('account.id'),
                            $now,
                            'NEW_STATION',
                            $request->get('connectionName'),
                            $request->get('connectionPhone')
                        );

                        # 修改设备对应入所单
                        RepairBaseNewStationOrderEntireInstance::with([])->whereIn('old_entire_instance_identity_code', $new_station_entire_instance_identity_codes)->update(['updated_at' => $now, 'in_warehouse_sn' => $in_warehouse_sn]);

                        return response()->json(['message' => '入所成功']);
                    };

                    # 出所
                    $out = function () use ($request, $work_area_id, $now, $new_station_order) {
                        # 读取未出所设备
                        $new_station_entire_instances = RepairBaseNewStationOrderEntireInstance::with([])
                            ->where('out_warehouse_sn', '')
                            ->where('out_sn', $request->get('serialNumber'))
                            ->get();
                        if ($new_station_entire_instances->isEmpty()) return response()->json(['message' => '没有添加任何设备'], 404);

                        # 设备出所
                        $out_warehouse_sn = WarehouseReportFacade::batchOutWithEntireInstanceIdentityCodes(
                            $new_station_entire_instances->pluck('old_entire_instance_identity_code')->all(),
                            session('account.id'),
                            $now,
                            'NORMAL',
                            $request->get('connectionName'),
                            $request->get('connectionPhone')
                        );
                        # 修改新站入所设备出所单号
                        RepairBaseNewStationOrderEntireInstance::with([])->whereIn('id', $new_station_entire_instances->pluck('id'))->update(['out_warehouse_sn' => $out_warehouse_sn]);

                        # 出所设备位置对应表
                        $out_entire_instance_correspondences = [];
                        foreach ($new_station_entire_instances->pluck('old_entire_instance_identity_code') as $item)
                            $out_entire_instance_correspondences[] = [
                                'old' => $item,
                                'new' => $item,
                                'location' => '新站：无',
                                'station' => @$new_station_order->Station->name,
                                'out_warehouse_sn' => $out_warehouse_sn,
                                'account_id' => session('account.id'),
                            ];
                        DB::table('out_entire_instance_correspondences')->insert($out_entire_instance_correspondences);

                        # 设备解锁
                        EntireInstanceLock::freeLocks($new_station_entire_instances->pluck('old_entire_instance_identity_code')->all(), ['NEW_STATION']);

                        return response()->json(['message' => '出所成功']);
                    };

                    return $$func();
                };

                $high_frequency = function () use ($request) {
                    $now = date('Y-m-d H:i:s');
                    $in = function () use ($request, $now) {
                        # 获取高频修入所任务单
                        $high_frequency_order = RepairBaseHighFrequencyOrder::with([])->where('serial_number', $request->get('serialNumber'))->first();
                        if (!$high_frequency_order) return response()->json(['message' => '没有找到对应的高频修入所任务单'], 404);

                        # 获取该高频修入所任务单下所有已扫码，未入所设备
                        $high_frequency_entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([
                            'InOrder',
                            'OutOrder',
                        ])
                            ->where('in_scan', true)
                            ->where('in_warehouse_sn', '')
                            ->where('in_sn', $request->get('serialNumber'))
                            ->get();
                        if (!$high_frequency_entire_instances) return response()->json(['message' => '入所前请先扫码'], 404);

                        # 新建入所单
                        $warehouse_report = new WarehouseReport();
                        $warehouse_report->fill([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'processor_id' => session('account.id'),
                            'processed_at' => $now,
                            'connection_name' => $request->get('connectionName'),
                            'connection_phone' => $request->get('connectionPhone'),
                            'type' => 'HIGH_FREQUENCY',
                            'direction' => 'IN',
                            'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('WAREHOUSE_IN'),
                            'work_area_id' => $high_frequency_order->work_area_id,
                        ])->saveOrFail();

                        $warehouse_report_entire_instances = [];
                        $entire_instance_logs = [];
                        foreach ($high_frequency_entire_instances as $high_frequency_entire_instance) {
                            # 入所设备
                            $warehouse_report_entire_instances[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'warehouse_report_serial_number' => $warehouse_report_sn,
                                'entire_instance_identity_code' => $high_frequency_entire_instance->old_entire_instance_identity_code,
                            ];
                            # 设备日志
                            $entire_instance_logs[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'name' => '高频修：入所',
                                'description' => 'IN',
                                'entire_instance_identity_code' => $high_frequency_entire_instance->old_entire_instance_identity_code,
                                'type' => 1,
                                'url' => "/warehouse/report/{$warehouse_report_sn}?show_type=D&direction=IN",
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$high_frequency_entire_instance->InOrder->station_code ?? '',
                            ];
                        }
                        DB::table('warehouse_report_entire_instances')->insert($warehouse_report_entire_instances);  # 入所单设备
                        EntireInstanceLogFacade::makeBatchUseArray($entire_instance_logs);  # 日志
                        EntireInstanceLock::freeLocks(array_pluck($entire_instance_logs, 'old_entire_instance_identity_code'), ['HIGH_FREQUENCY']);  # 设备解锁
                        DB::table('entire_instances')->whereIn('identity_code', $high_frequency_entire_instances->pluck('old_entire_instance_identity_code')->all())->update(['updated_at' => $now, 'status' => 'FIXED']);  # 修改设备状态
                        DB::table('repair_base_high_frequency_order_entire_instances')->whereIn('id', $high_frequency_entire_instances->pluck('id')->all())->update(['updated_at' => $now, 'in_warehouse_sn' => $warehouse_report_sn]);  # 高频修设备填充入所单号

                        return response()->json(['message' => '入所成功']);
                    };

                    $out = function () use ($request, $now) {
                        # 获取高频修出所任务单
                        $high_frequency_order = RepairBaseHighFrequencyOrder::with([])->where('serial_number', $request->get('serialNumber'))->first();
                        if (!$high_frequency_order) return response()->json(['message' => '没有找到对应的高频修入所任务单'], 404);

                        # 获取该高频修出所任务单下所有已扫码，未出所设备
                        $high_frequency_entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([
                            'InOrder',
                            'OutOrder',
                        ])
                            ->where('out_scan', true)
                            ->where('out_warehouse_sn', '')
                            ->where('out_sn', $request->get('serialNumber'))
                            ->get();
                        if (!$high_frequency_entire_instances) return response()->json(['message' => '出所前请先扫码'], 404);

                        # 新建出所单
                        $warehouse_report = new WarehouseReport();
                        $warehouse_report->fill([
                            'created_at' => $now,
                            'updated_at' => $now,
                            'processor_id' => session('account.id'),
                            'processed_at' => $now,
                            'connection_name' => $request->get('connectionName'),
                            'connection_phone' => $request->get('connectionPhone'),
                            'type' => 'HIGH_FREQUENCY',
                            'direction' => 'OUT',
                            'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
                            'work_area_id' => $high_frequency_order->work_area_id,
                        ])->saveOrFail();

                        $warehouse_report_entire_instances = [];
                        $entire_instance_logs = [];
                        $out_entire_instance_correspondences = [];
                        foreach ($high_frequency_entire_instances as $high_frequency_entire_instance) {
                            # 出所设备
                            $warehouse_report_entire_instances[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'warehouse_report_serial_number' => $warehouse_report_sn,
                                'entire_instance_identity_code' => $high_frequency_entire_instance->new_entire_instance_identity_code,
                            ];
                            # 设备日志
                            $entire_instance_logs[] = [
                                'created_at' => $now,
                                'updated_at' => $now,
                                'name' => '高频修：出所',
                                'description' => 'OUT',
                                'entire_instance_identity_code' => $high_frequency_entire_instance->new_entire_instance_identity_code,
                                'type' => 1,
                                'url' => "/warehouse/report/{$warehouse_report_sn}?show_type=D&direction=OUT",
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$high_frequency_entire_instance->OutOrder->station_code ?? '',
                            ];
                            # 新老设备位置对照表
                            $out_entire_instance_correspondences[] = [
                                'old' => $high_frequency_entire_instance->old_entire_instance_identity_code,
                                'new' => $high_frequency_entire_instance->new_entire_instance_identity_code,
                                'location' => $high_frequency_entire_instance->maintain_location_code ?
                                    $high_frequency_entire_instance->maintain_location_code :
                                    $high_frequency_entire_instance->maintain_location_code .
                                    $high_frequency_entire_instance->crossroad_number .
                                    $high_frequency_entire_instance->source .
                                    $high_frequency_entire_instance->source_traction .
                                    $high_frequency_entire_instance->source_crossroad_number .
                                    $high_frequency_entire_instance->traction .
                                    $high_frequency_entire_instance->open_direction .
                                    $high_frequency_entire_instance->said_rod,
                                'station' => $high_frequency_entire_instance->station_name,
                                'out_warehouse_sn' => $warehouse_report_sn,
                                'account_id' => session('account.id'),
                            ];
                        }
                        DB::table('warehouse_report_entire_instances')->insert($warehouse_report_entire_instances);  # 入所单设备
                        EntireInstanceLogFacade::makeBatchUseArray($entire_instance_logs);  # 日志
                        EntireInstanceLock::freeLocks(array_pluck($entire_instance_logs, 'new_entire_instance_identity_code'), ['HIGH_FREQUENCY']);  # 设备解锁
                        DB::table('entire_instances')->whereIn('identity_code', $high_frequency_entire_instances->pluck('new_entire_instance_identity_code')->all())->update(['updated_at' => $now, 'status' => 'TRANSFER_IN']);  # 修改设备状态
                        DB::table('repair_base_high_frequency_order_entire_instances')->whereIn('id', $high_frequency_entire_instances->pluck('id')->all())->update(['updated_at' => $now, 'out_warehouse_sn' => $warehouse_report_sn]);  # 高频修设备填充出所单号
                        DB::table('out_entire_instance_correspondences')->insert($out_entire_instance_correspondences);  # 新老设备位置对照表

                        return response()->json(['message' => '出所成功']);
                    };

                    $func = strtolower($request->get('direction'));
                    return $$func();
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });


        } catch (\Throwable $th) {
            return response()->json(['message' => '异常错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 标记子任务完成
     * @param Request $request
     * @return mixed
     */
    final public function putFinish(Request $request)
    {
        $new_station = function () use ($request) {
            try {
                $new_station_order = RepairBaseNewStationOrder::with([])->where('serial_number', $request->get('newStationOrderSn'))->firstOrFail();
                $new_station_order->fill(['status' => 'DONE'])->saveOrFail();

                $response = CurlHelper::init([
                    'headers' => $this->_auth,
                    'url' => "{$this->_root_url}/{$this->_spas_url}/finish/{$request->get('subTaskId')}",
                    'method' => CurlHelper::PUT,
                    'contents' => [
                        'sender_id' => session('account.id'),
                        'sender_affiliation' => env('ORGANIZATION_CODE'),
                        'finish_message' => $request->get('finishMessage'),
                    ]
                ]);
                if ($response['code'] > 399) return response()->json($response['body'], $response['code']);

                return response()->json(['message' => '编辑成功']);
            } catch (BadRequestException $e) {
                return response()->json(['message' => '数据中台链接失败', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
            } catch (\Throwable $e) {
                return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
            }
        };

        $func = strtolower($request->get('type'));
        return $$func();
    }

    /**
     * 生成各个工区子任务页面
     * @param int $main_task_id
     * @return Factory|JsonResponse|RedirectResponse|View
     */
    final public function getMakeSubTask105(int $main_task_id)
    {
        try {
            # 获取主任务详情
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/main/{$main_task_id}",
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], 500);
            $main_task_response['body']['data']['models'] = json_decode($main_task_response['body']['data']['models'], true);

            # 获取人员列表
            $accounts = DB::table('accounts')->pluck('nickname', 'id');

            return view('TemporaryTask.Production.Sub.create', [
                'main_task' => $main_task_response['body']['data'],
                'accounts' => $accounts,
            ]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '数据中台链接失败');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * 生成各个工区子任务
     *
     * @param Request $request
     * @param int $main_task_id
     * @return JsonResponse|RedirectResponse
     */
    final public function postMakeSubTask105(Request $request, int $main_task_id)
    {
        try {
            # 获取主任务详情
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/main/{$main_task_id}",
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], 500);
            $main_task_response['body']['data']['models'] = json_decode($main_task_response['body']['data']['models'], true);
            $models = $main_task_response['body']['data']['models'];

            # 获取人员列表
            $sub_tasks = [
                '转辙机工区' => [
                    'account' => null,
                    'contents' => null,
                ],
                '继电器工区' => [
                    'account' => null,
                    'contents' => null,
                ],
                '综合工区' => [
                    'account' => null,
                    'contents' => null,
                ],
            ];

            # 创建子任务
            if (isset($models['转辙机工区']) && !empty($models['转辙机工区'])) {
                $account = DB::table('accounts')->where('deleted_at', null)->where('work_area', 1)->where('temp_task_position', 'WorkArea')->first();  # 获取工区工长
                if (!$account) return response()->json(['message' => '未找到转辙机工区工长'], 404);
                $sub_tasks['转辙机工区']['account'] = $account->id;
                $sub_tasks['转辙机工区']['contents'] = [
                    'main_task_id' => $main_task_response['body']['data']['id'],
                    'receiver_id' => $account->id,
                    'receiver_affiliation' => $main_task_response['body']['data']['paragraph_code'],
                    'receiver_work_area_id' => 1,
                    'receiver_work_area_name' => '转辙机工区',
                ];
            }
            if (isset($models['继电器工区']) && !empty($models['继电器工区'])) {
                $account = DB::table('accounts')->where('deleted_at', null)->where('work_area', 2)->where('temp_task_position', 'WorkArea')->first();  # 获取工区工长
                if (!$account) return response()->json(['message' => '未找到继电器工区工长'], 404);
                $sub_tasks['继电器工区']['account'] = $account->id;
                $sub_tasks['继电器工区']['contents'] = [
                    'main_task_id' => $main_task_response['body']['data']['id'],
                    'receiver_id' => $account->id,
                    'receiver_affiliation' => $main_task_response['body']['data']['paragraph_code'],
                    'receiver_work_area_id' => 2,
                    'receiver_work_area_name' => '继电器工区',
                ];
            }
            if (isset($models['综合工区']) && !empty($models['综合工区'])) {
                $account = DB::table('accounts')->where('deleted_at', null)->where('work_area', 3)->where('temp_task_position', 'WorkArea')->first();  # 获取工区工长
                if (!$account) return response()->json(['message' => '未找到综合工区工长'], 404);
                $sub_tasks['综合工区']['account'] = $account->id;
                $sub_tasks['综合工区']['contents'] = [
                    'main_task_id' => $main_task_response['body']['data']['id'],
                    'receiver_id' => $account->id,
                    'receiver_affiliation' => $main_task_response['body']['data']['paragraph_code'],
                    'receiver_work_area_id' => 3,
                    'receiver_work_area_name' => '综合工区',
                ];
            }

            $ret = '';
            foreach ($sub_tasks as $work_area_name => $sub_task) {
                if ($sub_task['account'] !== null) {
                    $response = CurlHelper::init([
                        'headers' => $this->_auth,
                        'url' => "{$this->_root_url}/{$this->_spas_url}",
                        'method' => CurlHelper::POST,
                        'contents' => $sub_task['contents'],
                    ]);
                    $ret .= $response['code'] > 399 ? "{$work_area_name}：失败\r\n" : "{$work_area_name}：成功\r\n";
                    $sub_tasks[$work_area_name]['response'] = $response;
                }
            }

            return response()->json(['message' => $ret, 'sub_tasks' => $sub_tasks]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '数据中台链接失败');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * 执行任务页面
     * @param int $sub_task_id
     * @return Factory|RedirectResponse|View
     */
    final public function getProcess(int $sub_task_id)
    {
        try {
            # 获取子任务详情
            $sub_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$sub_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['message_id' => request('message_id')],
            ]);
            if ($sub_task_response['code'] > 399) return back()->with('danger', '获取子任务详情失败');

            # 读取主任务详情
            $main_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/temporaryTask/production/main/{$sub_task_response['body']['data']['main_task']}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['message_id' => request('message_id')],
            ]);
            if ($main_task_response['code'] > 399) return back()->with('danger', '获取主任务详情失败');
            $main_task_response['body']['data']['models'] = json_decode($main_task_response['body']['data']['models'], true);
            $model_codes = [];
            foreach ($main_task_response['body']['data']['models'] as $model) foreach ($model as $item) $model_codes[] = $item['code3'];

            # 统计已经完成的数量
            $model_codes_str = implode("', '", $model_codes);
            $model_codes_fixed_count = [];
            foreach (DB::select("select model_unique_code, count(model_unique_code) as c from temporary_task_production_sub_instances
where sub_task_id = ?
and work_area_name = ?
and model_unique_code in ('{$model_codes_str}')
group by model_unique_code", [$sub_task_id, session('account.work_area')]) as $item) {
                $model_codes_fixed_count[$item->model_unique_code] = $item->c;
            }

            # 获取本工区当前子任务已经添加的设备
            $entire_instances = DB::table('temporary_task_production_sub_instances as t')
                ->select(['t.entire_instance_identity_code', 'ei.model_name', 't.id'])
                ->join(DB::raw('entire_instances as ei'), 't.entire_instance_identity_code', '=', 'ei.identity_code')
                ->where('t.sub_task_id', $sub_task_id)
                ->orderByDesc('t.id')
                ->where('t.work_area_name', session('account.work_area'))
                ->paginate(50);

            return view('TemporaryTask.Production.Sub.process', [
                'sub_task' => $sub_task_response['body']['data'],
                'main_task' => $main_task_response['body']['data'],
                'entire_instances' => $entire_instances,
                'model_codes' => TextHelper::toJson($model_codes),
                'model_codes_fixed_count' => $model_codes_fixed_count,
            ]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '数据中台链接失败');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * 扫码填充任务
     * @param Request $request
     * @param int $sub_task_id
     * @return JsonResponse
     */
    final public function postProcess(Request $request, int $sub_task_id)
    {
        try {
            # 查看设备是否重复
            $repeat = DB::table('temporary_task_production_sub_instances')
                ->where('sub_task_id', $sub_task_id)
                ->where('entire_instance_identity_code', $request->get('entireInstanceIdentityCode'))
                ->first();
            if ($repeat) return response()->json(['message' => '重复扫描'], 403);

            # 获取设备详情
            $entire_instance = EntireInstance::with([])
                ->where('identity_code', $request->get('entireInstanceIdentityCode'))
                // ->where('status', 'FIXED')
                ->firstOrFail(['identity_code', 'model_unique_code', 'model_name']);

            # 检查设备是否属于任务型号组中
            if (!in_array($entire_instance['model_unique_code'], json_decode($request->get('modelCodes'), true))) return response()->json(['message' => "本次任务中没有{$entire_instance['model_name']}的设备"], 404);

            # 保存到数据库
            DB::table('temporary_task_production_sub_instances')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'main_task_id' => $request->get('mainTaskId'),
                'sub_task_id' => $sub_task_id,
                'entire_instance_identity_code' => $entire_instance['identity_code'],
                'work_area_name' => session('account.work_area'),
                'model_unique_code' => $entire_instance['model_unique_code'],
                'model_name' => $entire_instance['model_name'],
            ]);
            return response()->json(['message' => '添加成功']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '设备不存在或设备状态不是成品'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 删除子任务中的成品设备
     * @param $id
     * @return JsonResponse
     */
    final public function deleteCutEntireInstance($id)
    {
        DB::table('temporary_task_production_sub_instances')->where('id', $id)->delete();
        return response()->json(['message' => '删除成功']);
    }

    /**
     * 计划 页面
     */
    final public function getPlan(int $sub_task_id)
    {
        if (request('download') == 1) {
            try {
                $cell_key = [
                    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
                    'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
                    'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
                    'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
                ];

                $work_area = session('account.work_area');

                ExcelWriteHelper::download(
                    function ($excel) use ($cell_key, $sub_task_id) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        $fs = FileSystem::init(__FILE__);

                        # 获取工区人员
                        $accounts = DB::table('accounts')
                            ->where('deleted_at', null)
                            ->where('work_area', array_flip(Account::$WORK_AREAS)[session('account.work_area')])
                            ->where('supervision', 0)
                            ->pluck('nickname', 'id');
                        # 加载基础数据
                        $plan = $fs->setPath(storage_path("app/工区任务/{$sub_task_id}.json"))->fromJson();

                        # 总合计
                        $total_plan = 0;

                        # 定义首行
                        $col = 2;
                        $current_sheet->setCellValue("A1", "型号/人员");
                        $current_sheet->setCellValue("B1", "合计");
                        $current_sheet->getColumnDimension('A')->setWidth(20);
                        foreach ($accounts as $account_nickname) {
                            $current_sheet->setCellValue("{$cell_key[$col]}1", $account_nickname);
                            $current_sheet->getColumnDimension("{$cell_key[$col]}")->setWidth(15);
                            $col++;
                        }

                        # 获取当月计划
                        $row = 2;
                        foreach ($plan as $item) {
                            $col = 2;
                            $current_sheet->setCellvalue("A{$row}", $item['name3']);  # 型号列
                            $current_sheet->setCellValue("B{$row}", $item['plan']);  # 合集列
                            $total_plan += $item['plan'];
                            foreach ($accounts as $account_nickname) {
                                $current_sheet->setCellValue("{$cell_key[$col]}{$row}", key_exists($account_nickname, $item['accounts']) ? intval($item['accounts'][$account_nickname]) : 0);
                                $col++;
                            }
                            $row++;
                        }
                        return $excel;
                    },
                    "{$work_area}：临时检修任务工作分配"
                );
            } catch (Exception $exception) {
                return back()->with('info', $exception->getMessage());
            }
        }

        # 获取子任务
        $curl = new Curl();
        $curl->setHeaders([
            'Username' => $this->_spas_username,
            'Password' => $this->_spas_password,
        ]);
        $curl->get("{$this->_root_url}/{$this->_spas_url}/{$sub_task_id}");
        if ($curl->error) return back()->with('danger', $curl->response);
        $sub_task = (array)$curl->response->data;

        # 获取主任务
        $curl->get("{$this->_root_url}/temporaryTask/production/main/{$sub_task['main_task']}");
        if ($curl->error) return back()->with('danger', $curl->response);
        $main_task = (array)$curl->response->data;
        $curl->close();

        $main_task['models'] = json_decode($main_task['models'], true);

        # 获取工区人员
        $accounts = DB::table('accounts')
            ->where('deleted_at', null)
            ->where('work_area', array_flip(Account::$WORK_AREAS)[session('account.work_area')])
            ->where('supervision', 0)
            ->pluck('nickname', 'id');

        # 获取计划
        $plan_file = storage_path("app/工区任务/{$sub_task['id']}.json");
        if (is_file($plan_file)) {
            $plan = json_decode(file_get_contents($plan_file), true);
        } else {
            foreach ($main_task['models'][session('account.work_area')] as $item) $plan[$item['code3']] = array_merge($item, ['accounts' => new stdClass()]);
        }

        // foreach($plan as $item){
        //     dump($item);
        // }
        // dd();

        # 人员合计
        $account_plan_total = [];
        foreach ($accounts as $account_nickname) $account_plan_total[$account_nickname] = 0;
        foreach ($plan as $item) foreach ($item['accounts'] as $account_nickname => $value) $account_plan_total[$account_nickname] += $value;

        return view('TemporaryTask.Production.Sub.plan', [
            'main_task' => $main_task,
            'sub_task' => $sub_task,
            'accounts' => $accounts,
            'plan' => $plan,
            'plan_as_json' => json_encode($plan),
            'mission_as_json' => json_encode($main_task['models'][$sub_task['receiver_work_area_name']]),
            'account_plan_total' => $account_plan_total,
        ]);
    }

    /**
     * 计划 保存
     */
    final public function postPlan(Request $request, int $sub_task_id)
    {
        if (!is_dir(storage_path("app/工区任务"))) mkdir(storage_path("app/工区任务"));
        $save_ret = file_put_contents(storage_path("app/工区任务/{$sub_task_id}.json"), json_encode($request->all(), 256));
        return response()->json(['message' => '保存成功']);
    }

    /**
     * 下载Excel
     */
    final public function makeExcelWithPlan(int $sub_task_id)
    {
    }

    /**
     * 确认任务收到
     * @param int $sub_task_id
     * @return JsonResponse
     */
    final public function putChecked(int $sub_task_id)
    {
        try {

            $update_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/checked/{$sub_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::PUT,
            ]);
            if ($update_response['code'] != 200) return response()->json($update_response['body'], $update_response['code']);

            return response()->json(['message' => '确认收到']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 打印页面
     * @param string $serial_number
     * @return JsonResponse
     */
    final public function getPrintLabel(string $serial_number)
    {
        try {
            return DB::transaction(function () use ($serial_number) {
                $new_station = function () use ($serial_number) {
                    $in = function () use ($serial_number) {
                        $entire_instances = RepairBaseNewStationOrderEntireInstance::with([
                            'InOrder',
                            'OldEntireInstance'
                        ])
                            ->when(
                                request('search_content'),
                                function ($query) {
                                    return $query
                                        ->whereHas('OldEntireInstance', function ($EntireInstance) {
                                            $EntireInstance->where('identity_code', request('search_content'))
                                                ->orWhere('serial_number', request('search_content'));
                                        });
                                }
                            )
                            ->where('in_sn', $serial_number)
                            ->paginate();

                        return view('TemporaryTask.Production.Sub.printLabelIn_NEW_STATION', [
                            'entire_instances' => $entire_instances,
                            'in_sn' => $serial_number,
                        ]);
                    };

                    $func = strtolower(request('direction'));
                    return $$func();
                };

                $high_frequency = function () use ($serial_number) {
                    $in = function () use ($serial_number) {
                        $entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([
                            'InOrder',
                            'OldEntireInstance'
                        ])
                            ->when(
                                request('search_content'),
                                function ($query) {
                                    return $query
                                        ->whereHas('OldEntireInstance', function ($EntireInstance) {
                                            $EntireInstance->where('identity_code', request('search_content'))
                                                ->orWhere('serial_number', request('search_content'));
                                        });
                                }
                            )
                            ->where('in_sn', $serial_number)
                            ->paginate();

                        return view('TemporaryTask.Production.Sub.printLabelIn_HIGH_FREQUENCY', [
                            'entire_instances' => $entire_instances,
                            'in_sn' => $serial_number,
                        ]);
                    };

                    $out = function () use ($serial_number) {
                        # 获取子任务
                        $high_frequency_order = RepairBaseHighFrequencyOrder::with([])
                            ->where('serial_number', $serial_number)
                            ->first();
                        if (!$high_frequency_order) return back()->with('danger', '高频修任务单不存在');

                        $entire_instances = RepairBaseHighFrequencyOrderEntireInstance::with([
                            'OldEntireInstance',
                            'NewEntireInstance',
                        ])
                            ->where('out_sn', $serial_number)
                            ->get();

                        $plan_sum = collect(DB::select("
select count(*) as aggregate,ei.model_name
from `repair_base_high_frequency_order_entire_instances` as `oei`
inner join entire_instances ei on `ei`.`identity_code` = `oei`.old_entire_instance_identity_code
where `oei`.out_sn = ?
group by `ei`.`model_name`",
                            [$serial_number]))
                            ->pluck('aggregate', 'model_name')
                            ->sum();

                        $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($serial_number);
                        $usable_entire_instance_sum = $usable_entire_instances->sum(function ($value) {
                            return $value->count();
                        });

                        $old_count = DB::table('repair_base_high_frequency_order_entire_instances')->where('out_sn', $serial_number)->count();
                        $new_count = DB::table('repair_base_high_frequency_order_entire_instances')->where('out_sn', $serial_number)->where('new_entire_instance_identity_code', '<>', '')->count();
                        $is_all_bound = (($new_count === $old_count) && ($old_count > 0));  # 是否已经全部绑定

                        return view('TemporaryTask.Production.Sub.printLabelOut_HIGH_FREQUENCY', [
                            'entire_instances' => $entire_instances,
                            'usable_entire_instances' => $usable_entire_instances,
                            'out_sn' => $serial_number,
                            'is_all_bound' => $is_all_bound,
                            'plan_sum' => $plan_sum,
                            'usable_entire_instance_sum' => $usable_entire_instance_sum,
                        ]);
                    };

                    $func = strtolower(request('direction'));
                    return $$func();
                };

                $func = strtolower(request('type'));
                return $$func();
            });
        } catch (\Throwable $th) {
            dd($th->getMessage(), $th->getFile(), $th->getLine());
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 根据出所单号，获取该入所单
     * @param string $out_sn
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    final private function _getUsableEntireInstancesWithOutSn(string $out_sn)
    {
        $must_warehouse_location = false;  # 必须有位置编号

        $out_order = DB::table('repair_base_high_frequency_orders')
            ->where('serial_number', $out_sn)
            ->first(['in_sn']);
        if (!$out_order) throw new \Exception('出所单不存在', 404);
        if (!$out_order->in_sn) throw new \Exception('没有对应的入所单', 404);

        # 获取可用的新设备
        return DB::table('entire_instances as ei')
            ->select(['ei.identity_code', 'ei.model_name', 'ei.location_unique_code'])
            ->where('status', 'FIXED')
            ->when($must_warehouse_location, function ($query) {
                return $query
                    ->where('location_unique_code', '<>', null)
                    ->where('location_unique_code', '<>', '');
            })
            ->whereNotIn('identity_code', DB::table('entire_instance_locks')
                ->where('lock_name', 'HIGH_FREQUENCY')
                ->pluck('entire_instance_identity_code')
                ->toArray())
            ->whereNotIn('identity_code', DB::table('repair_base_high_frequency_order_entire_instances')->pluck('old_entire_instance_identity_code')->all())
            ->whereIn('model_name', DB::table('repair_base_high_frequency_order_entire_instances as oei')
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                ->where('oei.in_sn', $out_order->in_sn)
                ->groupBy('ei.model_name')
                ->pluck('ei.model_name')
                ->toArray())
            ->get()
            ->groupBy('model_name');
    }

    /**
     * 自动分配新设备到老设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $old_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([
                        'OldEntireInstance'
                    ])
                        ->where('out_sn', $request->get('outSn'))
                        ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                        ->firstOrFail();

                    $usable_entire_instance = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'))
                        ->get($old_entire_instance->OldEntireInstance->model_name);
                    if (is_null($usable_entire_instance)) return response()->json(['message' => '没有可替换的设备'], 404);

                    # 设备加锁
                    EntireInstanceLock::setOnlyLock(
                        $usable_entire_instance->first()->identity_code,
                        ['HIGH_FREQUENCY'],
                        "设备器材：{$usable_entire_instance}，在高频修出所中被使用。详情：高频修{$request->get('outSn')}",
                        function () use ($old_entire_instance, $usable_entire_instance) {
                            $old_entire_instance->fill(['new_entire_instance_identity_code' => $usable_entire_instance->first()->identity_code])->saveOrFail();
                        }
                    );

                    return response()->json(['message' => '绑定成功']);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '老设备不存在']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '绑定失败', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }

    /**
     * 全选自动分配新设备到老设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postAutoBindEntireInstances(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $usable_entire_instances = $this->_getUsableEntireInstancesWithOutSn($request->get('outSn'));
                    if (is_null($usable_entire_instances)) return response()->json(['message' => '没有可替换的设备'], 404);

                    $out_order = DB::table('repair_base_high_frequency_orders')->where('serial_number', $request->get('outSn'))->first(['in_sn']);
                    if (!$out_order) return response()->json(['没有找到对应的高频修出所计划'], 404);

                    $old_entire_instances = DB::table('repair_base_high_frequency_order_entire_instances as oei')
                        ->select(['ei.identity_code', 'ei.model_name'])
                        ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'oei.old_entire_instance_identity_code')
                        ->where('in_sn', $out_order->in_sn)
                        ->where('new_entire_instance_identity_code', '')
                        ->get()
                        ->groupBy('model_name')
                        ->all();

                    $new_entire_instance_identity_codes = [];
                    $entire_instance_locks = [];
                    foreach ($old_entire_instances as $model_name => $entire_instances) {
                        foreach ($entire_instances as $entire_instance) {
                            if ($usable_entire_instances->get($entire_instance->model_name)) {
                                if (!$usable_entire_instance = @$usable_entire_instances->get($entire_instance->model_name)->shift()->identity_code) continue;
                                DB::table('repair_base_high_frequency_order_entire_instances')
                                    ->where('in_sn', $out_order->in_sn)
                                    ->where('old_entire_instance_identity_code', $entire_instance->identity_code)
                                    ->update(['new_entire_instance_identity_code' => $usable_entire_instance]);

                                $new_entire_instance_identity_codes[] = $usable_entire_instance;
                                $entire_instance_locks[$usable_entire_instance] = "设备器材：{$usable_entire_instance}，在高频修出所中被使用。详情：高频修{$out_order->serial_number}";
                            }
                        }
                    }

                    # 设备加锁
                    EntireInstanceLock::setOnlyLocks($new_entire_instance_identity_codes, ['HIGH_FREQUENCY'], $entire_instance_locks);

                    return response()->json(['message' => '全部自动绑定成功']);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '老设备不存在']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '绑定失败', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]]);
        }
    }

    /**
     * 绑定新设备到老设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postBindEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $old_entire_instance = RepairBaseHighFrequencyOrderEntireInstance::with([])
                        ->where('out_sn', $request->get('outSn'))
                        ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                        ->firstOrFail();

                    # 如果存在新设备，先给新设备解锁
                    if ($old_entire_instance->new_entire_instance_identity_code) {
                        EntireInstanceLock::freeLock(
                            $old_entire_instance->new_entire_instance_identity_code,
                            ['HIGH_FREQUENCY'],
                            function () use ($request, $old_entire_instance) {
                                # 设备加锁
                                EntireInstanceLock::setOnlyLock(
                                    $request->get('newIdentityCode'),
                                    ['HIGH_FREQUENCY'],
                                    "设备器材：{$request->get('newIdentityCode')}，在高频修出所中被使用。详情：高频修{$request->get('outSn')}",
                                    function () use ($request, $old_entire_instance) {
                                        $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();
                                    }
                                );
                            }
                        );
                        return response()->json(['message' => '改绑成功']);
                    } else {
                        # 设备加锁
                        EntireInstanceLock::setOnlyLock(
                            $request->get('newIdentityCode'),
                            ['HIGH_FREQUENCY'],
                            "设备器材：{$request->get('newIdentityCode')}，在高频修出所中被使用。详情：高频修{$request->get('outSn')}",
                            function () use ($request, $old_entire_instance) {
                                $old_entire_instance->fill(['new_entire_instance_identity_code' => $request->get('newIdentityCode')])->saveOrFail();
                            }
                        );
                        return response()->json(['message' => '绑定成功']);
                    }
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '老设备不存在']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $th) {
            return response()->json(['message' => '绑定失败', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]]);
        }
    }

    /**
     * 删除绑定新设备到老设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteBindEntireInstance(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $ei = RepairBaseHighFrequencyOrderEntireInstance::with([])
                        ->where('old_entire_instance_identity_code', $request->get('oldIdentityCode'))
                        ->where('out_sn', $request->get('outSn'))
                        ->firstOrFail();

                    # 设备解锁
                    EntireInstanceLock::freeLock(
                        $ei->new_entire_instance_identity_code,
                        ['HIGH_FREQUENCY'],
                        function () use ($ei) {
                            $ei->fill(['new_entire_instance_identity_code' => ''])->saveOrFail();
                        }
                    );
                    return response()->json(['message' => '解绑成功']);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '设备不存在'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '保存失败', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }

    /**
     * 删除全选绑定新设备到老设备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteBindEntireInstances(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $high_frequency = function () use ($request) {
                    $out_order = RepairBaseHighFrequencyOrder::with([
                        'OutEntireInstances',
                    ])
                        ->where('serial_number', $request->get('outSn'))
                        ->first();
                    if (!$out_order) return response()->json(['没有找到对应的高频修出所计划'], 404);

                    # 解锁设备
                    $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all();
                    $ret = EntireInstanceLock::freeLocks(
                        $out_order->OutEntireInstances->pluck('new_entire_instance_identity_code')->all(),
                        ['HIGH_FREQUENCY'],
                        function () use ($out_order) {
                            DB::table('repair_base_high_frequency_order_entire_instances')
                                ->where('in_sn', $out_order->in_sn)
                                ->update(['new_entire_instance_identity_code' => '']);
                        }
                    );

                    return response()->json(['message' => '解绑成功', 'details' => $ret]);
                };

                $func = strtolower($request->get('type'));
                return $$func();
            });
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '设备不存在'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getLine(), $e->getFile()]], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => '保存失败', 'details' => [$th->getMessage(), $th->getLine(), $th->getFile()]], 403);
        }
    }
}
