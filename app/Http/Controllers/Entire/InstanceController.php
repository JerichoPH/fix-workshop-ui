<?php

namespace App\Http\Controllers\Entire;

use App\Exceptions\ExcelInException;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\KindsFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\OrganizationFacade;
use App\Facades\QueryBuilderFacade;
use App\Facades\SuPuRuiApi;
use App\Facades\TextFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\EntireInstanceRequest;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceExcelEditReport;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\FixWorkflowProcess;
use App\Model\Maintain;
use App\Model\WorkArea;
use App\Serializers\EntireInstanceSerializer;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Throwable;

/**
 * Class InstanceController
 * @example 01：入库单 02：出库单 03：检修工单
 * @package App\Http\Controllers\Entire
 */
class InstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            list($dateMadeAtOrigin, $dateMadeAtFinish) = explode('~', request('date_made_at', '{$now} 00:00:00~{$now} 23:59:59'));
            list($dateCreatedAtOrigin, $dateCreatedAtFinish) = explode('~', request('date_created_at', '{$now} 00:00:00~{$now} 23:59:59'));
            list($dateNextFixingDayOrigin, $dateNextFixingDayFinish) = explode('~', request('date_next_fixing_day', '{$now} 00:00:00~{$now} 23:59:59'));

            $statuses = collect(EntireInstance::$STATUSES);
            $factories = @Factory::with([])->get() ?: collect([]);
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

            $db_Q = EntireInstanceSerializer::ins()->GenerateQueryRelationShipQ()
                ->selectRaw(implode(',', [
                    'ei.identity_code',
                    'ei.factory_name',
                    'ei.maintain_workshop_name',
                    'ei.maintain_station_name',
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
                    // 'ei.fix_cycle_value as ei_fix_cycle_value',
                    'em.fix_cycle_value as em_fix_cycle_value',
                    'sm.fix_cycle_value as sm_fix_cycle_value',
                    'l.name as l_name',
                ]))
                ->orderByDesc('ei.identity_code');
            $db_S = EntireInstanceSerializer::ins()->GenerateQueryRelationShipS()
                ->selectRaw(implode(',', [
                    'ei.identity_code',
                    'ei.factory_name',
                    'ei.maintain_workshop_name',
                    'ei.maintain_station_name',
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
                    // 'ei.fix_cycle_value as ei_fix_cycle_value',
                    'em.fix_cycle_value as em_fix_cycle_value',
                    'em.fix_cycle_value as sm_fix_cycle_value',
                    'l.name as l_name',
                ]))
                ->orderByDesc('ei.identity_code');
            // dd(QueryBuilderFacade::unionAllToSql($db_Q, $db_S));
            $entire_instances = QueryBuilderFacade::unionAll($db_Q, $db_S)->paginate(100);

            return view(request('is_iframe') == 1 ? 'Entire.Instance.index3' : 'Entire.Instance.index2', [
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
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    final public function create()
    {
        try {
            $categories = Category::with([])->get();
            $entire_models = EntireModel::with([])->where('is_sub_model', false)->get();
            /*
             * 必填
             * 种类型
             * 供应商
             * 所编号
             */
            /*
             * 非必填
             * 出厂日期
             * 寿命（年）：计算报废日期，如不填则不计算
             * 上次出所日期
             * 上次安装日期（如果没有上次安装日期，将以上次出所日期为准）：计算下次周期修时间
             * 周期修（年）：计算下次周期修时间，如不填则按照类型周期修为准
             */
            return view('Entire.Instance.create', [
                'categories' => $categories,
                'entire_models' => $entire_models,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * 整件检修入所页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getFixing()
    {
        try {
            $entireInstance = EntireInstance::where("identity_code", request("entireInstanceIdentityCode"))->firstOrFail();
            $accounts = Account::orderByDesc("id")->pluck("nickname", "id");
            return view('Entire.Instance.fixing')
                ->with("accounts", $accounts)
                ->with("entireInstanceIdentityCode", request("entireInstanceIdentityCode"))
                ->with("entireInstance", $entireInstance);
        } catch (ModelNotFoundException $exception) {
            return back()->with("danger", "数据不存在");
        } catch (\Exception $exception) {
            return back()->with("danger", "意外错误");
        }
    }

    /**
     * 整件检修入所
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    final public function postFixing(Request $request)
    {
        try {
            DB::transaction(function () {
                DB::table('entire_instances')
                    ->where('identity_code', request('entireInstanceIdentityCode'))
                    ->update([
                        'updated_at' => date('Y-m-d'),
                        'fix_workflow_serial_number' => null,
                        'status' => 'FIXING',
                        'in_warehouse' => false
                    ]);

                $newWarehouseReportSerialNumber = CodeFacade::makeSerialNumber('IN');
                $warehouseReport = [
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                    'processor_id' => session('account.id'),
                    'processed_at' => date('y-m-d'),
                    'connection_name' => '',
                    'connection_phone' => '',
                    'type' => 'FIXING',
                    'direction' => 'IN',
                    'serial_number' => $newWarehouseReportSerialNumber,
                ];
                DB::table('warehouse_reports')->insert($warehouseReport);

                $warehouseReportEntireInstance = [
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                    'warehouse_report_serial_number' => $newWarehouseReportSerialNumber,
                    'entire_instance_identity_code' => request('entireInstanceIdentityCode'),
                ];
                DB::table('warehouse_report_entire_instances')->insert($warehouseReportEntireInstance);

                EntireInstanceLogFacade::makeOne(
                    session('account.id'),
                    '',
                    '待修',
                    request('entireInstanceIdentityCode'),
                    1,
                    "/warehouse/report/{$newWarehouseReportSerialNumber}"
                );
            });

            return Response::make("入所成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 入库单个设备（新入所）
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        try {
            $newEntireInstanceIdentityCode = WarehouseReportFacade::buyInOnce($request);
            return back()->with("success", '<h3>入所成功&nbsp;&nbsp;[<a href="' . url('search', $newEntireInstanceIdentityCode) . '">点击查看</a>]</h3>');
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with("danger", "数据不存在");
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return back()->withInput()->with("danger", "{$eMsg}<br>{$eCode}<br>{$eLine}<br>{$eFile}");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $entireModelUniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show(string $entireModelUniqueCode)
    {
        if (request()->has('date')) {
            list($originAt, $finishAt) = explode('~', request('date'));
        } else {
            $originAt = Carbon::now()->startOfMonth()->toDateString();
            $finishAt = Carbon::now()->endOfMonth()->toDateString();
        }

        try {
            $statuses = EntireInstance::$STATUSES;

            $entireInstances = DB::table("entire_instances as ei")
                ->select([
                    "ei.id",
                    "fw.id as fw_id",
                    "ei.identity_code",
                    "ei.model_name",
                    "ei.status",
                    "ei.factory_name",
                    "ei.factory_device_code",
                    "ei.maintain_station_name",
                    "ei.maintain_location_code",
                    "ei.crossroad_number",
                    "ei.traction",
                    "ei.open_direction",
                    "ei.line_name",
                    "ei.said_rod",
                    "ei.is_main",
                    "ei.installed_at",
                    "ei.fix_workflow_serial_number",
                    "fw.status as fw_status",
                ])
                ->leftJoin(DB::raw("warehouse_report_entire_instances wre"), "wre.entire_instance_identity_code", "=", "ei.identity_code")
                ->leftJoin(DB::raw("warehouse_reports wr"), "wr.serial_number", "=", "wre.warehouse_report_serial_number")
                ->leftJoin(DB::raw("fix_workflows fw"), "fw.serial_number", "=", "ei.fix_workflow_serial_number")
                ->where("ei.deleted_at", null)
                ->where("ei.status", "<>", "SCRAP")
                ->where(function ($query) use ($entireModelUniqueCode) {
                    $query->where('ei.model_unique_code', $entireModelUniqueCode)
                        ->orWhere('ei.entire_model_unique_code', $entireModelUniqueCode);
                })
                ->when(
                    request("status", null) !== null,
                    function ($query) {
                        return $query->where("ei.status", request("status"));
                    }
                )
                ->when(
                    request("date_type") == "create",
                    function ($query) use ($originAt, $finishAt) {
                        return $query->whereBetween("ei.created_at", [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request("date_type") == "in",
                    function ($query) use ($originAt, $finishAt) {
                        return $query->whereBetween("ei.created_at", [$originAt, $finishAt]);
                    }
                )
                ->when(
                    request("date_type") == "fix",
                    function ($query) use ($originAt, $finishAt) {
                        return $query->whereBetween("fw.created_at", [$originAt, $finishAt]);
                    }
                )
                ->groupBy(["ei.id"])
                ->orderByDesc("ei.id")
                ->paginate();

            $entireModel = EntireModel::where("unique_code", $entireModelUniqueCode)->firstOrFail(["name", "unique_code", "category_unique_code"]);

            session()->put("currentCategoryUniqueCode", $entireModel->category_unique_code);

            return view("Entire.Instance.show", [
                "entireInstances" => $entireInstances,
                "statuses" => $statuses,
                "entireModel" => $entireModel,
                "originAt" => $originAt,
                "finishAt" => $finishAt,
            ]);
        } catch (\Exception $exception) {
            return back()->with("danger", $exception->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $identityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($identityCode)
    {
        try {
            $entireInstance = EntireInstance::with([
                'Category',
                'EntireModel',
                'PartInstances',
                'PartInstances.PartModel',
                'FixWorkflow',
                'Station',
                'Station.Parent',
                'WarehouseReportByOut',
            ])
                ->where('identity_code', $identityCode)
                ->firstOrFail();

            # 获取同所编号数量
            $sameSerialNumberCount = $entireInstance->serial_number ? EntireInstance::with([])->where('serial_number', $entireInstance->serial_number)->where('deleted_at', null)->count() : 0;

            # 获取同组合位置数量
            $sameMaintainLocationCodeCount = $entireInstance->maintain_location_code ? EntireInstance::with([])
                ->where('maintain_location_code', $entireInstance->maintain_location_code)
                ->where('maintain_station_name', $entireInstance->maintain_station_name)
                ->count() : 0;

            # 获取同道岔号数量
            $sameCrossroadNumberCount = $entireInstance->crossroad_number ? EntireInstance::with([])
                ->where('crossroad_number', $entireInstance->crossroad_number)
                ->where('maintain_station_name', $entireInstance->maintain_station_name)
                ->count() : 0;

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

            # 车间（包括专业车间）
            $scene_workshops = DB::table("maintains")->whereIn("type",["SCENE_WORKSHOP","WORKSHOP"])->get()->pluck("name","unique_code")->all();

            // if ($entireInstance->category_unique_code) switch ($entireInstance->category_unique_code) {
            //     case 'S03':
            //         $work_area_id = 1;
            //         break;
            //     case 'Q01':
            //         $work_area_id = 2;
            //         break;
            //     default:
            //         $work_area_id = 3;
            //         break;
            // }

            // $fixers = Account::with([])->where('work_area', $work_area_id)->pluck('nickname', 'id');
            // $checkers = Account::with([])->where('work_area', $work_area_id)->pluck('nickname', 'id');
            $fixers = $checkers = Account::with([])->pluck('nickname', 'id');

            # 获取最后一次检修人
            $fixer = FixWorkflowProcess::with([])->select('processor_id')->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'FIX_AFTER')->first();
            # 获取最后一次验收人
            $checker = FixWorkflowProcess::with([])->select('processor_id')->where('fix_workflow_serial_number', $entireInstance->fix_workflow_serial_number)->orderByDesc('id')->where('stage', 'CHECKED')->first();

            # 供应商列表
            $statisticsRootDir = storage_path('app/basicInfo');
            $factories = file_exists("{$statisticsRootDir}/factories.json") ?
                array_pluck(json_decode(file_get_contents("{$statisticsRootDir}/factories.json")), 'name') :
                Factory::with([])->get()->pluck('name');

            $entire_model = DB::table('entire_models')->select(['fix_cycle_value'])->whereNull('deleted_at')->where('unique_code', $entireInstance->entire_model_unique_code)->first();
            $fix_cycle_value = @$entire_model->fix_cycle_value ?: 0;

            return view('Entire.Instance.edit', [
                'entireInstance' => $entireInstance,
                'statuses' => EntireInstance::$STATUSES,
                'stations_as_json' => $stations->groupBy('scene_workshop_unique_code')->toJson(),
                'scene_workshops' => $scene_workshops,
                'fixers' => $fixers,
                'checkers' => $checkers,
                'fixer' => $fixer->processor_id ?? 0,
                'checker' => $checker->processor_id ?? 0,
                'sameSerialNumberCount' => $sameSerialNumberCount ?? 0,
                'sameMaintainLocationCodeCount' => $sameMaintainLocationCodeCount ?? 0,
                'sameCrossroadNumberCount' => $sameCrossroadNumberCount ?? 0,
                'factories' => $factories,
                'fix_cycle_value' => $fix_cycle_value,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with("danger", $e->getMessage());
        } catch (Exception $e) {
            return back()->with("danger", $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $identity_code
     * @return JsonResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    final public function update(Request $request, $identity_code)
    {
        try {
            $v = Validator::make($request->all(), EntireInstanceRequest::$RULES, EntireInstanceRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            $entire_instance = EntireInstance::with(['EntireModel', 'FixWorkflow',])
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            $made_at = null;
            $scarping_at = null;
            if ($request->get('made_at')) {
                try {
                    $made_at = Carbon::parse($request->get('made_at'));
                } catch (Exception $e) {
                    return JsonResponseFacade::errorValidate('生产日期格式不正确');
                }

                // 重新计算寿命
                $life_year = $entire_instance->EntireModel->life_year ?? ($entire_instance->EntireModel->Parent->life_year ?? 0);
                if ($life_year) $scarping_at = $made_at->copy()->addYears($life_year);
            }

            // 计算下次周期修
            $next_fixing_datum = [];
            if ($request->get('last_out_at')) {
                try {
                    $last_out_at = Carbon::parse($request->get('last_out_at'));
                } catch (Exception $e) {
                    return JsonResponseFacade::errorValidate('出所日期格式不正确');
                }
                $next_fixing_datum = EntireInstanceFacade::nextFixingTime($entire_instance, $last_out_at->format('Y-m-d'));
            }

            $update_datum = $request->except(
                'last_installed_at',
                'fixer',
                'checker',
                'is_update_fix_date',
                'scene_workshop_unique_code',
                'maintain_station_name',
                'last_fix_workflow_time',
                "fix_cycle_value"
            );
            $update_datum["last_fix_workflow_at"] = rtrim($request->get("last_fix_workflow_at") . " " . $request->get("last_fix_workflow_time"));
            if ($entire_instance->SubModel->custom_fix_cycle) {
                // 自定义周期修型号
                $update_datum["fix_cycle_value"] = $request->get("fix_cycle_value");
            }

            $scene_workshop = null;
            if ($request->get('scene_workshop_unique_code')) {
                $scene_workshop = Maintain::with([])->where('unique_code', $request->get('scene_workshop_unique_code'))->first();
                if (!$scene_workshop) return response()->json(['message' => '现场车间不存在']);
                $update_datum['maintain_workshop_name'] = $scene_workshop->name;
            } else {
                $update_datum['maintain_workshop_name'] = '';
            }

            $station = null;
            if ($request->get('maintain_station_name')) {
                $station = Maintain::with(['Parent'])->where('name', $request->get('maintain_station_name'))->first();
                if (!$station) return response()->json(['message' => "没有找到车站：{$request->get('maintain_station_name')}"]);
                if (!@$station->Parent) return response()->json(['message' => '车站数据有误，没有找到对应的现场车间'], 404);
                $scene_workshop = $station->Parent;
                $update_datum['maintain_workshop_name'] = $scene_workshop->name;
                $update_datum['maintain_station_name'] = $station->name;
            } else {
                $update_datum['maintain_station_name'] = '';
            }

            if (!empty($next_fixing_datum)) $update_datum = array_merge($update_datum, $next_fixing_datum);
            $last_installed_time = 0;
            if ($request->get('last_installed_at')) {
                try {
                    $last_installed_time = Carbon::parse($request->get('last_installed_at'))->timestamp;
                } catch (Exception $e) {
                    return JsonResponseFacade::errorValidate('上道日期格式不正确');
                }
            }
            if ($last_installed_time > 0) $update_datum['installed_at'] = date("Y-m-d H:i:s", $last_installed_time);
            if ($made_at) $update_datum['made_at'] = $made_at->format('Y-m-d');
            if ($scarping_at) $update_datum['scarping_at'] = $scarping_at->format('Y-m-d');
            $entire_instance->fill($update_datum)->saveOrFail();

            # 如果有检修人和检测人，则创建空检测单
            if ($request->get('is_update_fix_date')) {
                if ($request->get('fixer') && $request->get('last_fix_workflow_at')) {
                    FixWorkflowFacade::mockEmpty(
                        $entire_instance,
                        @$update_datum["last_fix_workflow_at"] ?: "",
                        @$update_datum["last_fix_workflow_at"] ?: "",
                        $request->get('fixer'),
                        $request->get('checker')
                    );
                } else {
                    return JsonResponseFacade::errorValidate('检修人、检修时间必须一起填写');
                }
            }

            return JsonResponseFacade::ok("编辑成功");
        } catch (ModelNotFoundException $exception) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (Throwable $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $identityCode
     * @return \Illuminate\Http\Response
     */
    final public function destroy($identityCode)
    {
        try {
            $entireInstance = EntireInstance::where("identity_code", $identityCode)->firstOrFail();
            $entireInstance->fill(["status" => "SCRAP"])->saveOrFail();

            return Response::make("报废成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 报废
     * @param $identityCode
     * @return \Illuminate\Http\Response
     */
    final public function scrap($identityCode)
    {
        try {
            $entireInstance = EntireInstance::where("identity_code", $identityCode)->firstOrFail();
            $entireInstance->fill(["status" => "SCRAP"])->saveOrFail();

            return Response::make("报废成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 入所页面
     * @param $entireInstanceIdentityCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getFixingIn($entireInstanceIdentityCode)
    {
        $accounts = DB::table("accounts")
            ->orderByDesc("id")
            ->where("deleted_at", null)
            ->where(function ($query) {
                $query->where("workshop_code", env("ORGANIZATION_CODE"))->orWhere("workshop_code", null);
            })
            ->pluck("nickname", "id");
        return view('Entire.Instance.fixingIn_ajax')
            ->with("entireInstanceIdentityCode", $entireInstanceIdentityCode)
            ->with("accounts", $accounts);
    }

    /**
     * 入所
     * @param Request $request
     * @param string $entireInstanceIdentityCode
     * @return \Illuminate\Http\Response
     */
    final public function postFixingIn(Request $request, string $entireInstanceIdentityCode)
    {
        try {
            # 获取检修单数据
            WarehouseReportFacade::inOnce($request, EntireInstance::where("identity_code", $entireInstanceIdentityCode)->firstOrFail());
            return Response::make("入所成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
        }
    }

    /**
     * 出库安装页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function getInstall()
    {
        try {
            $accounts = Account::with([])->where('work_area_unique_code', session('account.work_area_unique_code.unique_code'))->get();
            $scene_workshops = DB::table('maintains')->where('deleted_at', null)->where('parent_unique_code', env('ORGANIZATION_CODE'))->pluck('name', 'unique_code');


            return view('Entire.Instance.install', [
                'accounts' => $accounts,
                'scene_workshops' => $scene_workshops,

            ]);
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 出库安装
     * @param Request $request
     * @param string $entireInstanceIdentityCode
     * @return \Illuminate\Http\Response
     */
    final public function postInstall(Request $request, string $entireInstanceIdentityCode)
    {
        try {
            $entireInstance = EntireInstance::with(["EntireModel"])->where("identity_code", $entireInstanceIdentityCode)->firstOrFail();
            $ret = WarehouseReportFacade::outOnce($request, $entireInstance);
            // return response()->json($ret);

            return Response::make("出库成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("意外错误", 500);
        }
    }

    /**
     * 批量导入页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getBatch()
    {
        $accounts = Account::orderByDesc("id")->pluck("nickname", "id");
        return view("Entire.Instance.batch")
            ->with("accounts", $accounts);
    }

    /**
     * 批量导入
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Exception
     */
    final public function postBatch(Request $request)
    {
        if (!$request->hasFile("file")) return back()->with("error", "上传文件不存在");

        try {
            DB::beginTransaction();

            list($entireInstances, $partInstances, $suPuRuiInputs)
                = \App\Facades\EntireInstanceFacade::batchFromExcelWithNew($request, "file", "0");
            $entireInstanceCount = count($entireInstances);
            DB::table("entire_instances")->insert($entireInstances);  # 导入设备实例
            $entireInstanceIdentityCodes = collect($entireInstances)->pluck("identity_code")->toArray();  # 获取全部整件实例的唯一编号
            //            if ($request->get("auto_insert_fix_workflow")) FixWorkflowFacade::batchByEntireInstanceIdentityCodes($entireInstanceIdentityCodes);  # 如果需要自动创建检修单
            # 创建入所记录
            WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                $entireInstanceIdentityCodes,
                $request->get("processor_id"),
                $request->get("processed_at"),
                "BUY_IN",
                $request->get("connection_name"),
                $request->get("connection_phone")
            );

            # 如果存在部件，导入部件部分
            if ($request->get("has_part") && $partInstances != null) DB::table("part_instances")->insert($partInstances);

            # 调用速普瑞接口
            if (env("SUPURUI_API", 0) == 1) {
                if (array_key_exists("S", $suPuRuiInputs)) $suPuRuiInputResult_S = SuPuRuiApi::debug(true)->returnType("x2a")->insertEntireInstances_S($suPuRuiInputs["S"]);
                if (array_key_exists("Q", $suPuRuiInputs)) $suPuRuiInputResult_Q = SuPuRuiApi::debug(true)->returnType("x2a")->insertEntireInstances_Q($suPuRuiInputs["Q"]);
            }

            DB::commit();
            return back()->with("success", "成功导入：{$entireInstanceCount}条数据");
        } catch (ModelNotFoundException $exception) {
            DB::rollback();
            return $request->ajax() ?
                response()->make(env("APP_DEBUG") ?
                    "数据不存在：" . $exception->getMessage() :
                    "数据不存在", 404) :
                back()->withInput()->with("danger", env("APP_DEBUG") ?
                    "数据不存在：" . $exception->getMessage() :
                    "数据不存在");
        } catch (\Exception $exception) {
            DB::rollback();
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            //            dd("{$eMsg}\r\n{$eFile}\r\n{$eLine}");
            return $request->ajax() ?
                response()->make(env("APP_DEBUG") ?
                    "{$eMsg}\r\n{$eFile}\r\n{$eLine}" :
                    "意外错误", 500) :
                back()->withInput()->with("danger", env("APP_DEBUG") ?
                    "{$eMsg}<br>{$eFile}<br>{$eLine}" :
                    "意外错误");
        }
    }

    /**
     * 旧编号批量转新编号页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function getOldNumberToNew()
    {
        $entireInstances = EntireInstance::with(["Category"])->where("old_number", null)->paginate();
        return view('Entire.Instance.oldNumberToNew')
            ->with("entireInstances", $entireInstances);
    }

    /**
     * 旧编号批量转新编号
     */
    final public function postOldNumberToNew()
    {
        $entireInstances = EntireInstance::with(["Category"])->where("old_number", null)->get();
        $count = \App\Facades\EntireInstanceFacade::makeNewCode($entireInstances);
        return response()->make("成功转码：" . $count);
    }

    /**
     * 通过类型或子类获取供应商列表
     * @param string $entireModelUniqueCode
     * @return JsonResponse
     */
    final public function getFactoryByEntireModelUniqueCode(string $entireModelUniqueCode)
    {
        return response()->json(
            DB::table("pivot_entire_model_and_factories")
                ->join("factories", "name", "=", "factory_name")
                ->where("entire_model_unique_code", $entireModelUniqueCode)
                ->pluck("factories.name")
        );
    }

    /**
     * 批量上传页面
     */
    final public function getUpload()
    {
        try {
            $workAreas = array_flip([
                0 => '全部',
                1 => '转辙机工区',
                2 => '继电器工区',
                3 => '综合工区',
            ]);
            $currentWorkArea = $workAreas[session('account.work_area')];
            $downloadTypes = [
                '0101' => '转辙机上道、备品',
                '0102' => '转辙机成品、待修',
                '0201' => '继电器、综合上道、备品',
                '0202' => '继电器、综合成品、待修',
            ];

            # 下载
            if (request('download')) {
                # 下载Excel模板
                ExcelWriteHelper::download(function ($excel) use ($downloadTypes) {
                    $excel->setActiveSheetIndex(0);
                    $currentSheet = $excel->getActiveSheet();

                    # 字体颜色
                    $red = new \PHPExcel_Style_Color();
                    $red->setRGB('FF0000');

                    # 转辙机工区（转辙机上道、备品）
                    $makeExcel0101 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '车站*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('H1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('I1', '出所日期/上道日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('I1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('J1', '道岔号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('J1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('K1', '开向*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('K1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('L1', '线制*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('L1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('M1', '表示杆特征*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('M1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('N1', '道岔类型*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('N1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('O1', '转辙机组合类型*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('O1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('P1', '防挤压保护罩*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('P1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('Q1', '牵引*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('Q1')->getFont()->setColor($red);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('F2', 'ZD6-D', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', '常德', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('I2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellValueExplicit('J2', '4#', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('K2', '左/右', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('L2', '4线制', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('M2', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('N2', '道岔类型', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('O2', '转辙机组合类型', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('P2', '是/否', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('Q2', '2牵引', \PHPExcel_Cell_DataType::TYPE_STRING);

                        return $excel;
                    };

                    # 转辙机工区（转辙机成品、待修）
                    $makeExcel0102 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '检修日期', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('I1', '线制', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('J1', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('K1', '防挤压保护罩', \PHPExcel_Cell_DataType::TYPE_STRING);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('F2', 'ZD6-D', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellValueExplicit('I2', '4线制', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('J2', '表示杆特征', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('K2', '是/否', \PHPExcel_Cell_DataType::TYPE_STRING);

                        return $excel;
                    };

                    # 继电器综合（继电器综合上道、备品）
                    $makeExcel0201 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '车站*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('H1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('I1', '出所日期/上道日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('I1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('J1', '上道位置*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('J1')->getFont()->setColor($red);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('F2', 'JWXC-1000', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', '常德', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('I2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellValueExplicit('J2', '1-2-3-4', \PHPExcel_Cell_DataType::TYPE_STRING);

                        return $excel;
                    };

                    # 继电器综合（继电器综合成品、待修）
                    $makeExcel0202 = function () use ($excel, $currentSheet, $red) {
                        # 首行
                        $currentSheet->setCellValueExplicit('A1', '出厂编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('A1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('B1', '厂家*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('B1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('C1', '出厂日期*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('C1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('D1', '使用寿命(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('D1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('E1', '所编号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('E1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('F1', '型号*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('F1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('G1', '周期修(年)*', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->getStyle('G1')->getFont()->setColor($red);
                        $currentSheet->setCellValueExplicit('H1', '检修日期', \PHPExcel_Cell_DataType::TYPE_STRING);

                        # 第二行
                        $currentSheet->setCellValueExplicit('A2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('B2', '沈阳信号工厂', \PHPExcel_Cell_DataType::TYPE_STRING);
                        $currentSheet->setCellValueExplicit('C2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);
                        $currentSheet->setCellvalueExplicit('D2', 15, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellvalueExplicit('E2', '123456', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('F2', 'JWXC-1000', \PHPExcel_Cell_DataType::TYPE_STRING2);
                        $currentSheet->setCellvalueExplicit('G2', 0, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $currentSheet->setCellValueExplicit('H2', date('Y-m-d'), \PHPExcel_Cell_DataType::TYPE_NULL);

                        return $excel;
                    };

                    # 定义列宽
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(20);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(17);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(4))->setWidth(17);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(5))->setWidth(14);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(6))->setWidth(18);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(7))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(7))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(8))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(9))->setWidth(10);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(10))->setWidth(11);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(11))->setWidth(10);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(12))->setWidth(15);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(13))->setWidth(13);
                    $currentSheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(14))->setWidth(15);

                    $func = "makeExcel" . request('download');
                    return $$func();
                }, "批量导入模板(" . $downloadTypes[request('download')] . ")");
            }
            # 上传
            $accounts = Account::with([])
                ->when(session('account.work_area'), function ($query) use ($currentWorkArea) {
                    $query->where('work_area', $currentWorkArea);
                })
                ->get();
            return view('Entire.Instance.upload', [
                'currentWorkArea' => $currentWorkArea,
                'accounts' => $accounts,
            ]);
        } catch (\Exception $e) {
            //            dd(class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return redirect('/warehouse/report/scanBatch?direction=IN')->with('danger', '意外错误');
        }
    }

    /**
     * 批量上传
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUpload(Request $request)
    {
        try {
            # 导入类型
            switch ($request->get('type')) {
                case 'INSTALLED':
                case 'INSTALLING':
                    $type = '01';
                    break;
                case 'FIXED':
                case 'FIXING':
                    $type = '02';
                    break;
                default:
                    throw new ExcelInException("导入类型错误");
                    break;
            }
            $factoryNames = Factory::with([])->pluck('name')->toArray();  # 供应商列表
            $r = 1;  # 当前行
            $entireInstanceLogs = [];

            # 转辙机上道、备品
            $insert0101 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 车站* 出所日期/上道日期* 道岔号* 开向* 线制* 表示杆特征* 道岔类型* 转辙机组合类型* 防挤压保护罩* 牵引*
                        list(
                            $factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName, $cycleFixYear, $maintainStationName,
                            $lastOutAt, $crossroadNumber, $openDirection, $lineName, $saidRod, $crossroadType, $pointSwitchGroupType, $extrusionProtect, $traction
                            ) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $model = DB::table('part_models as pm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'pm.unique_code as mu',
                                'pm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('pm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('pm.name', $modelName)
                            ->where('em.is_sub_model', true)
                            ->first();
                        if (!$model) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        if (!$lastOutAt) throw new ExcelInException("第{$r}行错误：出所日期/上道日期不能为空");
                        $lastInstalledTime = strtotime($lastOutAt);
                        # 计算下次周期修时间
                        $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($model->cycle_fix_year ? $model->cycle_fix_year : 0);
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($cycleFixYear) {
                            $time = Carbon::parse($lastOutAt)->addYears($cycleFixYear);
                            $nextFixingTime = $time->timestamp;
                            $nextFixingMonth = $time->format('Y-m-d');
                            $nextFixingDay = $time->format('Y-m-d');
                        }
                        if (!$maintainStationName) throw new ExcelInException("第{$r}行错误：车站不能为空");
                        $maintain = DB::table('maintains as s')
                            ->select(['sc.name as scn', 'sc.unique_code as scu', 's.name as sn', 's.unique_code as su'])
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                            ->where('s.deleted_at', null)
                            ->where('sc.deleted_at', null)
                            ->where('s.name', $maintainStationName)
                            ->where('s.type', 'STATION')
                            ->where('sc.type', 'SCENE_WORKSHOP')
                            ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                            ->first();
                        if (!$maintain) throw new ExcelInException("第{$r}行错误：没有找到车站");

                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $model->mu,
                            'model_name' => $model->mn,
                            'entire_model_unique_code' => $model->emu,
                            'category_unique_code' => $model->cu,
                            'category_name' => $model->cn,
                            'last_out_at' => $lastOutAt,
                            'installed_at' => date("Y-m-d H:i:s", $lastInstalledTime),
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'crossroad_number' => $crossroadNumber,
                            'open_direction' => $openDirection,
                            'line_name' => $lineName,
                            'said_rod' => $saidRod,
                            'crossroad_type' => $crossroadType,
                            'point_switch_group_type' => $pointSwitchGroupType,
                            'extrusion_protect' => $extrusionProtect == '是',
                            'traction' => $traction,
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                            'maintain_workshop_name' => $maintain->scn,
                            'maintain_station_name' => $maintain->sn,
                        ];
                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::generateIdentityCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；报废日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];

                        return $entireInstance;
                    });
            };

            # 转辙机成品、待修
            $insert0102 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 检修日期 线制 表示杆特征 防挤压保护罩
                        list(
                            $factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName,
                            $cycleFixYear, $fixedAt, $lineName, $saidRod, $extrusionProtect
                            ) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $partModel = DB::table('part_models as pm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'pm.unique_code as mu',
                                'pm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('pm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('pm.name', $modelName)
                            ->where('em.is_sub_model', true)
                            ->first();
                        if (!$partModel) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($fixedAt) {
                            # 计算下次周期修时间
                            $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($partModel->cycle_fix_year ? $partModel->cycle_fix_year : 0);
                            if ($cycleFixYear) {
                                $time = Carbon::parse($fixedAt)->addYears($cycleFixYear);
                                $nextFixingTime = $time->timestamp;
                                $nextFixingMonth = $time->format('Y-m-d');
                                $nextFixingDay = $time->format('Y-m-d');
                            }
                        }
                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $partModel->mu,
                            'model_name' => $partModel->mn,
                            'entire_model_unique_code' => $partModel->emu,
                            'category_unique_code' => $partModel->cu,
                            'category_name' => $partModel->cn,
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'line_name' => $lineName,
                            'said_rod' => $saidRod,
                            'extrusion_protect' => $extrusionProtect == '是',
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                        ];

                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::generateIdentityCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；报废日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];
                        return $entireInstance;
                    });
            };

            # 继电器上道、备品
            $insert0201 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 出所日期/上道日期* 上道位置*
                        list($factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName, $cycleFixYear, $maintainStationName, $lastOutAt, $maintainLocationCode) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $model = DB::table('entire_models as sm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'sm.unique_code as mu',
                                'sm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('sm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.is_sub_model', false)
                            ->where('sm.name', $modelName)
                            ->first();
                        if (!$model) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        if (!$lastOutAt) throw new ExcelInException("第{$r}行错误：出所日期/上道日期不能为空");
                        $lastInstalledTime = strtotime($lastOutAt);
                        # 计算下次周期修时间
                        $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($model->cycle_fix_year ? $model->cycle_fix_year : 0);
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($cycleFixYear) {
                            $time = Carbon::parse($lastOutAt)->addYears($cycleFixYear);
                            $nextFixingTime = $time->timestamp;
                            $nextFixingMonth = $time->format('Y-m-d');
                            $nextFixingDay = $time->format('Y-m-d');
                        }
                        if (!$maintainStationName) throw new ExcelInException("第{$r}行错误：车站不能为空");
                        $maintain = DB::table('maintains as s')
                            ->select(['sc.name as scn', 'sc.unique_code as scu', 's.name as sn', 's.unique_code as su'])
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                            ->where('s.deleted_at', null)
                            ->where('sc.deleted_at', null)
                            ->where('s.name', $maintainStationName)
                            ->where('s.type', 'STATION')
                            ->where('sc.type', 'SCENE_WORKSHOP')
                            ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                            ->first();
                        if (!$maintain) throw new ExcelInException("第{$r}行错误：没有找到车站");
                        if (!$maintainLocationCode) throw new ExcelInException("第{$r}行错误：上道位置不能为空");

                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $model->mu,
                            'model_name' => $model->mn,
                            'entire_model_unique_code' => $model->mu,
                            'category_unique_code' => $model->cu,
                            'category_name' => $model->cn,
                            'last_out_at' => $lastOutAt,
                            'installed_at' => date("Y-m-d", $lastInstalledTime),
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                            'maintain_workshop_name' => $maintain->scn,
                            'maintain_station_name' => $maintain->sn,
                            'maintain_location_code' => $maintainLocationCode,
                        ];
                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::generateIdentityCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；报废日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];

                        return $entireInstance;
                    });
            };

            # 继电器成品、待修
            $insert0202 = function () use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                return ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->withSheetIndex(0, function ($row) use ($request, &$r, $factoryNames, &$entireInstanceLogs) {
                        # 预检查
                        ++$r;
                        # 出厂编号* 厂家* 出厂日期* 使用寿命(年)* 所编号* 型号* 周期修(年)* 出所日期/上道日期* 上道位置*
                        list($factoryDeviceCode, $factoryName, $madeAt, $lifeTimeYear, $serialNumber, $modelName, $cycleFixYear, $fixedAt) = $row;
                        if (!$factoryDeviceCode) throw new ExcelInException("第{$r}行错误：出厂编号不能为空");
                        if (!$factoryName) throw new ExcelInException("第{$r}行错误：厂家不能为空");
                        if (!in_array($factoryName, $factoryNames)) throw new ExcelInException("第{$r}行错误：厂家不存在");
                        if (!$madeAt) throw new ExcelInException("第{$r}行错误：出厂日期不能为空");
                        if (!$lifeTimeYear || $lifeTimeYear < 1) throw new ExcelInException("第{$r}行错误：使用寿命不能为空或小于1");
                        # 计算报废日期
                        $scarpingAt = Carbon::parse($madeAt)->addYears($lifeTimeYear)->format('Y-m-d');
                        if (!$serialNumber) throw new ExcelInException("第{$r}行错误：所编号不能为空");
                        $repeatSerialNumber = DB::table('entire_instances as ei')->where('ei.deleted_at', null)->where('serial_number', $serialNumber)->first();
                        if ($repeatSerialNumber) throw new ExcelInException("第{$r}行错误：所编号重复（{$repeatSerialNumber->identity_code}）");
                        if (!$modelName) throw new ExcelInException("第{$r}行错误：型号不能为空");
                        $model = DB::table('entire_models as sm')
                            ->select([
                                'c.unique_code as cu',
                                'c.name as cn',
                                'em.unique_code as emu',
                                'em.name as emn',
                                'sm.unique_code as mu',
                                'sm.name as mn',
                                'em.fix_cycle_value as cycle_fix_year'
                            ])
                            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                            ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                            ->where('sm.deleted_at', null)
                            ->where('em.deleted_at', null)
                            ->where('c.deleted_at', null)
                            ->where('sm.is_sub_model', true)
                            ->where('em.is_sub_model', false)
                            ->where('sm.name', $modelName)
                            ->first();
                        if (!$model) throw new ExcelInException("第{$r}行错误：型号不存在或该型号没有对应的类型");
                        $cycleFixYear = intval($cycleFixYear);
                        if ($cycleFixYear < 0) throw new ExcelInException("第{$r}行错误：周期修(年)不能为空或小于0");
                        if (!$fixedAt) throw new ExcelInException("第{$r}行错误：出所日期/上道日期不能为空");
                        $lastInstalledTime = strtotime($fixedAt);
                        # 计算下次周期修时间
                        $cycleFixYear = $cycleFixYear ? $cycleFixYear : ($model->cycle_fix_year ? $model->cycle_fix_year : 0);
                        $nextFixingTime = null;
                        $nextFixingMonth = null;
                        $nextFixingDay = null;
                        if ($cycleFixYear) {
                            $time = Carbon::parse($fixedAt)->addYears($cycleFixYear);
                            $nextFixingTime = $time->timestamp;
                            $nextFixingMonth = $time->format('Y-m-d');
                            $nextFixingDay = $time->format('Y-m-d');
                        }

                        # 组合设备信息
                        $entireInstance = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'factory_device_code' => $factoryDeviceCode,
                            'factory_name' => $factoryName,
                            'made_at' => $madeAt,
                            'scarping_at' => $scarpingAt,
                            'serial_number' => $serialNumber,
                            'model_unique_code' => $model->mu,
                            'model_name' => $model->mn,
                            'entire_model_unique_code' => $model->mu,
                            'category_unique_code' => $model->cu,
                            'category_name' => $model->cn,
                            'last_out_at' => $fixedAt,
                            'installed_at' => date("Y-m-d", $lastInstalledTime),
                            'next_fixing_time' => $nextFixingTime,
                            'next_fixing_month' => $nextFixingMonth,
                            'next_fixing_day' => $nextFixingDay,
                            'status' => $request->get('type'),
                            'base_name' => env('ORGANIZATION_CODE'),
                        ];
                        if ($cycleFixYear) $entireInstance['fix_cycle_value'] = $cycleFixYear;
                        $entireInstance['identity_code'] = EntireInstance::generateIdentityCode($entireInstance['entire_model_unique_code']);

                        # 组合日志信息
                        $entireInstanceLogs[] = [
                            'created_at' => $madeAt,
                            'updated_at' => $madeAt,
                            'name' => '出厂',
                            'description' => "出厂日期：{$madeAt}；报废日期：{$scarpingAt}；",
                            'entire_instance_identity_code' => $entireInstance['identity_code'],
                            'type' => 0,
                            'url' => '',
                        ];

                        return $entireInstance;
                    });
            };

            $func = "insert{$request->get('workArea')}{$type}";
            $excel = $$func();
            if ($excel['fail']) throw new ExcelInException("Excel中存在：" . count($excel['fail']) . "条错误");

            DB::beginTransaction();
            DB::table('entire_instances')->insert($excel['success']);  # 写入设备
            if ($entireInstanceLogs) DB::table('entire_instance_logs')->insert($entireInstanceLogs);  # 写入日志
            $entireInstanceLogs = [];
            DB::commit();

            return back()->with('success', '成功写入：' . count($excel['success']) . '条');
        } catch (ExcelInException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', $e->getMessage());
        } catch (\Throwable $th) {
            dd($th->getMessage(), $th->getFile(), $th->getLine());
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * 批量删除
     * @param Request $request
     * @return JsonResponse
     */
    final public function postDelete(Request $request): JsonResponse
    {
        try {
            $deletedCount = EntireInstance::with([])
                ->whereIn('identity_code', $request->get('identityCodes'))
                ->update([
                    "deleted_at" => now(),
                    "deleter_id" => session("account.id"),
                ]);
            return response()->json(['message' => "成功删除{$deletedCount}条"]);
        } catch (Exception $e) {
            return response()->json(['message' => '异常错误', 'details' => [get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 回收站
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getTrashed()
    {
        try {
            $entireInstances = EntireInstance::with(["Deleter"])
                ->onlyTrashed()
                ->orderByDesc('deleted_at')
                ->paginate();

            return view('Entire.Instance.trashed', [
                'entireInstances' => $entireInstances,
            ]);
        } catch (Exception $e) {
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 恢复删除
     * @param Request $request
     * @return JsonResponse
     */
    final public function postRefresh(Request $request)
    {
        try {
            $refreshCount = EntireInstance::with([])->whereIn('identity_code', $request->get('identityCodes'))->restore();
            return response()->json(['message' => "成功恢复{$refreshCount}条"]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '数据不存在'], 404);
        } catch (\Throwable $th) {
            return response()->json(['msg' => '意外错误', 'details' => [$th->getMessage(), $th->getFile(), $th->getLine()]], 500);
        }
    }

    /**
     * 下载批量修改设备模板
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function getDownloadUploadEditDeviceExcelTemplate()
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', request('work_area_unique_code'))->first();
            if (!$work_area) return back()->with('danger', '工区参数错误');

            return EntireInstanceFacade::downloadUploadEditDeviceExcelTemplate($work_area);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量编辑设备页面
     */
    final public function getUploadEditDevice()
    {
        try {
            $work_area = WorkArea::with([])->where('unique_code', session('account.work_area_unique_code'))->first();
            if (!$work_area) return redirect('/')->with('danger', '当前用户没有工区');

            // 'pointSwitch', 'reply', 'synthesize', 'scene', 'powerSupplyPanel'

            switch ($work_area->type) {
                case 'pointSwitch':
                default:
                    $work_areas = WorkArea::with([])->get();
                    return view('Entire.Instance.uploadEditDevice', [
                        'work_areas' => $work_areas,
                    ]);
                // return redirect('/')->with('danger', '该暂时只对继电器、综合、电源屏工区开放');
                case 'reply':
                case 'synthesize':
                case 'powerSupplyPanel':
                    $work_areas = WorkArea::with([])->get();
                    return view('Entire.Instance.uploadEditDevice', [
                        'work_areas' => $work_areas,
                    ]);
            }
        } catch (Exception $e) {
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 上传批量编辑设备
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function postUploadEditDevice(Request $request)
    {
        try {
            if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
            if (!in_array($request->file('file')->getClientMimeType(), [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ])) return back()->withInput()->with('danger', '只能上传excel，当前格式：' . $request->file('file')->getClientMimeType());

            $work_area = WorkArea::with([])->where('unique_code', $request->get('work_area_unique_code'))->first();
            if (!$work_area) return back()->with('danger', '工区参数错误');

            // 保存文件
            $original_filename = $request->file('file')->getClientOriginalName();
            $original_extension = $request->file('file')->getClientOriginalExtension();
            $rand_str = TextFacade::rand('num', 6);
            $paragraph_code = env('ORGANIZATION_CODE');
            $today = now()->format('Ymd');
            $filename = $request->file('file')
                ->storeAs("/public/uploadEditExcel", "{$paragraph_code}{$today}{$rand_str}.{$original_extension}");

            // 设置目录权限
            exec('chmod 777 ' . storage_path('app/public/uploadEditExcel'));

            // 保存批量修改记录
            EntireInstanceExcelEditReport::with([])
                ->create([
                    'processor_id' => session('account.id'),
                    'work_area_unique_code' => session('account.work_area_unique_code'),
                    'filename' => $filename,
                    'original_filename' => $original_filename,
                ]);

            return EntireInstanceFacade::uploadEditDevice($request, $work_area->type);
        } catch (ExcelInException $e) {
            dd($e->getMessage());
            return back()->with('danger', $e->getMessage());
        } catch (Throwable $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量修改
     * @param Request $request
     * @return mixed
     */
    final public function putUpdateBatch(Request $request)
    {
        try {
            if (!$request->get('identity_codes')) return JsonResponseFacade::errorValidate('请勾选设备');
            $params = collect($request->except('identity_codes'))->filter(function ($value) {
                return !empty($value);
            });
            if ($params->isEmpty()) return JsonResponseFacade::errorValidate('没有需要修改的内容');
            if ($params->get('scene_workshop_unique_code')) {
                $scene_workshop = Maintain::with([])->where('type', 'SCENE_WORKSHOP')->where('unique_code', $params->get('scene_workshop_unique_code'))->first();
                if (!$scene_workshop) return JsonResponseFacade::errorValidate('所选现场车间不存在');
                $params->forget('scene_workshop_unique_code');
                $params['maintain_workshop_name'] = $scene_workshop->name;
            }
            if ($params->get('maintain_station_unique_code')) {
                $station = Maintain::with([])->where('type', 'STATION')->where('unique_code', $params->get('maintain_station_unique_code'))->first();
                if (!$station) return JsonResponseFacade::errorValidate('所选车站不存在');
                $params->forget('maintain_station_unique_code');
                $params['maintain_station_name'] = $station->name;
            }
            if ($params->get('installed_at')) {
                $params['installed_at'] = $params->get('installed_at');
            }

            // if ($params->get('next_fixing_time')) {
            //     $params['next_fixing_day'] = $params->get('next_fixing_time');
            //     $params['next_fixing_time'] = strtotime($params->get('next_fixing_time'));
            //     $params['next_fixing_month'] = Carbon::createFromTimestamp($params->get('next_fixing_time'))->startOfMonth()->format('Y-m-d');
            // }
            // if ($params->get('scraping_at')) {
            //     $params['scarping_at'] = $params->get('scraping_at');
            //     $params->forget('scraping_at');
            // }

            DB::begintransaction();
            // 执行批量修改
            EntireInstance::with([])
                ->whereIn('identity_code', $request->get('identity_codes'))
                ->update(array_merge(['updated_at' => now(),], $params->toArray()));

            $entire_instances = EntireInstance::with(['EntireModel', 'EntireModel.Parent',])->whereIn('identity_code', $request->get('identity_codes'))->get();
            $entire_instances->each(function ($entire_instance) use ($request) {
                // 重新计算报废日期
                if ($request->get('made_at')) {
                    try {
                        $made_at = $request->get('made_at');
                        $made_at = Carbon::parse($made_at);
                        $life_year = @$entire_instance->EntireModel->life_year ?: (@$entire_instance->EntireModel->Parent->life_year ?: 0);
                        if ($life_year) {
                            $scarping_at = $made_at->copy()->addYears($life_year)->format('Y-m-d');
                        } else {
                            $scarping_at = null;
                        }

                        $entire_instance->fill(['scarping_at' => $scarping_at,]);
                    } catch (Exception $e) {
                        throw new Exception('生产日期格式错误');
                    }
                }

                // 重新计算下次周期修
                if ($request->get('last_out_at')) {
                    $next_fixing_datum = EntireInstanceFacade::nextFixingTime($entire_instance, $request->get('last_out_at'));
                    $entire_instance->fill($next_fixing_datum);
                }
                $entire_instance->saveOrFail();
            });

            DB::commit();

            return JsonResponseFacade::ok('批量修改成功');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量修改历史记录
     */
    final public function getUploadEditHistory()
    {
        try {
            $work_area_unique_code = session('account.work_area_unique_code');
            $work_area = WorkArea::with([])->where('unique_code', $work_area_unique_code)->first();
            if (!$work_area) {
                return back()->with('danger', '当前用户没有所属工区');
            }

            $entire_instance_excel_edit_reports = EntireInstanceExcelEditReport::with([])
                ->where('work_area_unique_code', $work_area_unique_code)
                ->orderByDesc('created_at')
                ->paginate();

            return view('Entire.Instance.uploadEditHistory', ['entire_instance_excel_edit_reports' => $entire_instance_excel_edit_reports,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * get standby statistics
     * @param string $station_unique_code
     * @param string|null $type
     * @return array
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function getStandbyStatistics(string $entire_instance_identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->where('identity_code', $entire_instance_identity_code)
                ->firstOrFail();
            if (!@$entire_instance->Station) return JsonResponseFacade::errorEmpty('车站不存在');
            if (!@$entire_instance->Station->Parent) return JsonResponseFacade::errorEmpty('车站没有所属现场车间');
            $station = $entire_instance->Station;

            $getStatisticsDB = function (string $status = null) use ($entire_instance): array {
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
                    ->where('ei.model_unique_code', $entire_instance->model_unique_code)
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
                    ->where('ei.deleted_at', null)
                    ->where('ei.status', '<>', 'SCRAP')
                    ->where('ei.status', $status)
                    ->where('ei.model_unique_code', $entire_instance->model_unique_code)
                    ->whereNull('pm.deleted_at')
                    ->where('pc.is_main', true)
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->whereNull('c.deleted_at')
                    ->groupBy(['ei.model_unique_code', 'ei.model_name',]);
                return [$db_Q, $db_S];
            };

            $functions = [
                'station' => function () use ($station, $getStatisticsDB) {
                    list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                    $db_Q->where('s.unique_code', $station->unique_code);
                    $db_S->where('s.unique_code', $station->unique_code);
                    return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
                },
                'near_station' => function () use ($station, $getStatisticsDB) {
                    $distance_with_stations = DB::table('distance as d')
                        ->selectRaw(implode(',', [
                            'd.from_unique_code',
                            'd.to_unique_code',
                            'd.distance',
                            's.name',
                            'ws.unique_code as workshop_unique_code',
                            's.contact',
                            's.contact_phone',
                            's.lon',
                            's.lat',
                        ]))
                        ->selectRaw("d.from_unique_code,d.to_unique_code, d.distance, s.name, ws.unique_code as workshop_unique_code, s.contact, s.contact_phone, s.lon, s.lat")
                        ->join(DB::raw('maintains s'), 'd.to_unique_code', '=', 's.unique_code')
                        ->join(DB::raw('maintains ws'), 'ws.unique_code', '=', 's.parent_unique_code')
                        ->where('d.from_unique_code', $station->unique_code)
                        ->where('d.to_unique_code', '<>', $station->unique_code)
                        ->where('d.from_type', 'STATION')
                        ->where('d.to_type', 'STATION')
                        ->orderBy(DB::raw('d.distance+0'))
                        ->limit(2)
                        ->get()
                        ->toArray();

                    $near_station_statistics = [];
                    foreach ($distance_with_stations as $distance_with_station) {
                        list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                        $db_Q->where('s.unique_code', $distance_with_station->to_unique_code);
                        $db_S->where('s.unique_code', $distance_with_station->to_unique_code);
                        $near_station_statistics[$distance_with_station->to_unique_code] = [
                            'statistics' => ModelBuilderFacade::unionAll($db_Q, $db_S)->get(),
                            'unique_code' => $distance_with_station->to_unique_code,
                            'name' => $distance_with_station->name,
                            'distance' => $distance_with_station->distance,
                        ];
                    }

                    return $near_station_statistics;
                },
                'scene_workshop' => function () use ($station, $getStatisticsDB) {
                    list($db_Q, $db_S) = $getStatisticsDB('INSTALLING');
                    $db_Q->where('s.parent_unique_code', $station->parent_unique_code);
                    $db_S->where('s.parent_unique_code', $station->parent_unique_code);
                    return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
                },
                'fix_workshop' => function () use ($station, $getStatisticsDB) {
                    list($db_Q, $db_S) = $getStatisticsDB('FIXED');
                    return ModelBuilderFacade::unionAll($db_Q, $db_S)->get();
                }
            ];

            $distances = [
                'scene_workshop' => DB::table('distance')->where('from_unique_code', $station->unique_code)->where('to_unique_code', $station->parent_unique_code)->first(['distance'])->distance ?? 0,
                'fix_workshop' => DB::table('distance')->where('from_unique_code', $station->unique_code)->where('to_unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first(['distance'])->distance ?? 0
            ];

            $statistics['station'] = $functions['station']();
            $statistics['near_station'] = $functions['near_station']();
            $statistics['scene_workshop'] = $functions['scene_workshop']();
            $statistics['fix_workshop'] = $functions['fix_workshop']();
            $statistics['near_station_category_count'] = 0;
            foreach ($statistics['near_station'] as $near_station) {
                $statistics['near_station_category_count'] += count($near_station['statistics']) > 0 ? count($near_station['statistics']) : 1;
            }

            return JsonResponseFacade::data([
                'statistics' => $statistics,
                'distances' => $distances,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('设备器材不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }

    }
}
