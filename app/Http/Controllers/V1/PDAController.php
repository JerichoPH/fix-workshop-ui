<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\EmptyException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidateException;
use App\Facades\BreakdownLogFacade;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\JWTFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\TextFacade;
use App\Facades\WarehouseReportFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\RegisterRequest;
use App\Http\Requests\V1\LoginRequest;
use App\Model\Account;
use App\Model\Area;
use App\Model\CheckPlan;
use App\Model\CheckPlanEntireInstance;
use App\Model\CheckProject;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\EntireInstanceUseReport;
use App\Model\FixWorkflowProcess;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallShelf;
use App\Model\Install\InstallTier;
use App\Model\Line;
use App\Model\Maintain;
use App\Model\PartInstance;
use App\Model\Platoon;
use App\Model\Position;
use App\Model\RepairBaseBreakdownOrder;
use App\Model\RepairBaseBreakdownOrderEntireInstance;
use App\Model\SendRepair;
use App\Model\Shelf;
use App\Model\StationInstallLocationRecord;
use App\Model\Storehouse;
use App\Model\TakeStock;
use App\Model\TaskStationCheckEntireInstance;
use App\Model\TaskStationCheckOrder;
use App\Model\Tier;
use App\Model\V250TaskEntireInstance;
use App\Model\V250TaskOrder;
use App\Model\V250WorkshopOutEntireInstances;
use App\Model\Warehouse;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class PDAController extends Controller
{
    private $_statuses = null;

    public function __construct()
    {
        $this->_statuses = EntireInstance::$STATUSES;
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postRegister(Request $request): JsonResponse
    {
        try {
            $v = Validator::make($request->all(), RegisterRequest::$RULES, RegisterRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            $work_area = null;
            $workshop_unique_code = null;
            if ($request->get('work_area_unique_code')) {
                $work_area = WorkArea::with(['Workshop'])->where('unique_code', $request->get('work_area_unique_code'))->first();
                if (!$work_area) return JsonResponseFacade::errorEmpty('???????????????');
                if (!$work_area->Workshop) return JsonResponseFacade::errorEmpty("?????????{$work_area->name}?????????????????????????????????");
                $workshop_unique_code = $work_area->workshop_unique_code;
            }
            $station = null;
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->where('type', 'STATION')->first();
                if (!$station) return JsonResponseFacade::errorEmpty('???????????????');
                if (!$station->Parent) return JsonResponseFacade::errorEmpty("?????????{$station->name}?????????????????????????????????");
                if ($request->get('work_area_unique_code')) {
                    if ($station->parent_unique_code != $work_area->workshop_unique_code) return JsonResponseFacade::errorForbidden("?????????{$work_area->name}????????????{$station->name}???????????????????????????", $station, $work_area);
                }
                $workshop_unique_code = $station->parent_unique_code;
            }

            $account = Account::with([])
                ->create(array_merge($request->all(), [
                    'password' => bcrypt($request->get('password')),
                    'status_id' => 1,
                    'open_id' => md5(time() . $request->get('account') . mt_rand(1000, 9999)),
                    'work_area_unique_code' => @$work_area ? $work_area->unique_code : '',
                    'station_unique_code' => @$station ? $station->unique_code : '',
                    'workshop_unique_code' => $workshop_unique_code ?? '',
                    'identity_code' => mt_rand(0001, 9999),
                    'work_area' => 0,
                    'workshop_code' => env('ORGANIZATION_CODE'),
                    'rank' => $request->get('rank') ?? 'None',
                ]));

            // ?????????????????????
            DB::table('pivot_role_accounts')->insert(['rbac_role_id' => 1, 'account_id' => $account->id,]);

            return JsonResponseFacade::created([], '????????????');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @return JsonResponse
     */
    final public function getStations(): JsonResponse
    {
        try {
            return JsonResponseFacade::data(['stations' => ModelBuilderFacade::init(request(), Maintain::with([]))->all()]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????',], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine(),]], 500);
        }
    }

    /**
     * ????????????
     * @return JsonResponse
     */
    final public function getWorkAreas()
    {
        try {
            $work_areas = ModelBuilderFacade::init(request(), WorkArea::with([]))
                ->extension(function ($WorkArea) {
                    return $WorkArea->where('paragraph_unique_code', env('ORGANIZATION_CODE'));
                })
                ->all();

            return JsonResponseFacade::data(['work_areas' => $work_areas]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @return mixed
     */
    final public function getRanks()
    {
        try {
            return JsonResponseFacade::data(['ranks' => Account::$RANKS]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postLogin(Request $request): JsonResponse
    {
        try {
            // ????????????
            $v = Validator::make($request->all(), LoginRequest::$RULES, LoginRequest::$MESSAGES);
            if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

            // ????????????
            $account = Account::with([])->where('account', $request->get('account'))->firstOrFail();
            if (!Hash::check($request->get('password'), $account->password)) return JsonResponseFacade::errorUnauthorized('????????????????????????');

            // ??????jwt
            $payload = $account->toArray();
            unset($payload['password']);
            $jwt = JWTFacade::generate($payload);

            return JsonResponseFacade::created(
                [
                    'jwt' => $jwt,
                    'account' => $account
                ],
                '????????????'
            );
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return mixed
     */
    final public function anyMakeSign(Request $request): JsonResponse
    {
        try {
            $ret = [];
            $secretKey = 'TestSecretKey';
            $ret['step1'] = "??????SecretKey:{$secretKey}";
            $ret['step1'] = [
                '??????SecretKey',
                $secretKey,
            ];
            $data = $request->all();
            $ret['step2'] = [
                '??????????????????',
                $data,
            ];
            $data = array_filter($data);
            $ret['step3'] = [
                '????????????',
                $data,
            ];
            $data['secret_key'] = $secretKey;
            $ret['step4'] = [
                '???????????????SecretKey',
                $data
            ];
            ksort($data);
            $ret['step5'] = [
                '??????key??????????????????',
                $data,
            ];
            $urlQuery = http_build_query($data);
            $ret['step6'] = [
                '??????urlQuery',
                $urlQuery,
            ];
            $md5 = md5($urlQuery);
            $ret['step7'] = [
                'md5??????',
                $md5,
            ];
            $sign = strtoupper($md5);
            $ret['step8'] = [
                '?????????',
                $sign,
            ];
            return JsonResponseFacade::data($ret);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return mixed
     */
    final public function anyCheckSign(Request $request)
    {
        try {
            $ret = [];
            $data = $request->all();
            $ret['data'] = $data;
            $account = Account::with([])->where('access_key', $request->header('Access-Key'))->firstOrFail();
            $secretKey = $account->secret_key;
            $ret['secret_key'] = $secretKey;
            $data['secret_key'] = $secretKey;
            ksort($data);
            $ret['sorted'] = $data;
            $query = http_build_query($data);
            $ret['query'] = $query;
            $md5 = md5($query);
            $ret['md5'] = $md5;
            $sign = strtoupper($md5);
            $ret['sign'] = $sign;
            $ret['ret'] = boolval($sign == $request->header('Sign'));

            return JsonResponseFacade::data($ret);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('??????????????????');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????->????????????
     * @return JsonResponse
     */
    final public function getMaintains(): JsonResponse
    {
        try {
            $maintains = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->get()->toArray();
            foreach ($maintains as $v) {
                $station[] = [
                    'unique_code' => $v->unique_code,
                    'name' => $v->name,
                    'subset' => DB::table('maintains')->where('parent_unique_code', $v->unique_code)->where('type', 'STATION')->get(['unique_code', 'name'])->toArray()
                ];
            }
            return JsonResponseFacade::data($station);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????->?????????
     * @return JsonResponse
     */
    final public function getTypes(): JsonResponse
    {
        try {
            foreach (DB::table('categories')->get()->toArray() as $categoryKey => $category) {
                $types[$categoryKey] = [
                    'name' => $category->name,
                    'unique_code' => $category->unique_code,
                    'subset' => []
                ];
                foreach (DB::table('entire_models')->where('category_unique_code', $category->unique_code)->where('is_sub_model', 0)->get()->toArray() as $entireModelsKey => $entireModels) {
                    $types[$categoryKey]['subset'][$entireModelsKey] = [
                        'name' => $entireModels->name,
                        'unique_code' => $entireModels->unique_code,
                        'subset' => []
                    ];

                    $types[$categoryKey]['subset'][$entireModelsKey]['subset'] = DB::table('entire_models')->where('parent_unique_code', $entireModels->unique_code)->where('is_sub_model', 1)->get(['name', 'unique_code'])->toArray();

                    foreach (DB::table('part_models')->where('entire_model_unique_code', $entireModels->unique_code)->get()->toArray() as $partModelsKey => $partModels) {
                        $types[$categoryKey]['subset'][$entireModelsKey]['subset'][$partModelsKey] = [
                            'name' => $partModels->name,
                            'unique_code' => $partModels->unique_code,
                        ];
                    }
                }
            }
            return JsonResponseFacade::data($types);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????->????????????
     * @return JsonResponse
     */
    final public function getLocations(): JsonResponse
    {
        try {

            $locations = Storehouse::with([
                'subset',
                'subset.subset',
                'subset.subset.subset',
                'subset.subset.subset.subset',
                'subset.subset.subset.subset.subset'
            ])->get(['name', 'unique_code']);
            return JsonResponseFacade::data($locations);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????->????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopInOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('data');
            $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            if (empty($data)) return JsonResponseFacade::errorEmpty();
            $data = [
                'identity_code' => $data[0]->identity_code,
                'category_name' => $data[0]->category_name,
                'sub_model_name' => $data[0]->model_name,
                'status' => $this->_statuses[$data[0]->status]
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '?????????????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopIn(Request $request): JsonResponse
    {
        try {
            $contactName = $request->get('contact_name');
            $contactPhone = $request->get('contact_phone');
            $accountNickname = session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
            $identity_codes = $request->get('datas');
            $date = $request->get('date');
            $sign_image = $request->get('sign', '') ?: '';

            if (empty($identity_codes)) return JsonResponseFacade::errorValidate('??????????????????????????????');
            // if (!$sign_img) return JsonResponseFacade::errorValidate('????????????');

            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $identity_codes)
                ->get();

            DB::beginTransaction();
            $warehouse_report = WarehouseReport::with([])
                ->create([
                    'processor_id' => $accountId,
                    'processed_at' => $date,
                    'connection_name' => $contactName,
                    'connection_phone' => $contactPhone,
                    'type' => 'FIXING',
                    'direction' => 'IN',
                    'serial_number' => $warehouseReportSerialNumber = CodeFacade::makeSerialNumber('WAREHOUSE_IN'),
                    'work_area_id' => $workAreaId,
                    'work_area_unique_code' => session('account.work_area_unique_code'),
                    'sign_image' => $sign_image,
                ]);
            if ($sign_image) $warehouse_report->saveSignImage($sign_image);  // ?????????????????????

            # ???????????????
            $entireInstances->each(function (EntireInstance $entireInstance)
            use ($date, $accountNickname, $contactName, $contactPhone, $warehouseReportSerialNumber, $identity_codes) {
                $can_i_warehouse_in = $entireInstance->can_i_warehouse_in;
                if ($can_i_warehouse_in !== true) throw new ForbiddenException($can_i_warehouse_in);

                # ??????????????????????????????
                DB::table('warehouse_report_entire_instances')
                    ->insert([
                        'created_at' => $date,
                        'updated_at' => $date,
                        'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                    ]);

                # ????????????????????????
                EntireInstanceLog::with([])
                    ->create([
                        'name' => '??????',
                        'description' => implode('???', [
                            '????????????' . $accountNickname,
                            '????????????' . @$contactName ?: '???',
                            '???????????????' . @$contactPhone ?: '???',
                            '?????????' . @$entireInstance->last_maintain_station_name ?: ($entireInstance->maintain_station_name ?? ''),
                            '???????????????' . InstallPosition::getRealName(@$entireInstance->last_maintain_location_code ?: (@$entireInstance->maintain_location_code ?: ''), @$entireInstance->last_maintain_location_code ?: (@$entireInstance->maintain_location_code ?: ''))
                            . @$entireInstance->last_crossroad_number ?: (@$entireInstance->crossroad_number ?: '') . ' ' . @$entireInstance->last_open_direction ?: (@$entireInstance->open_direction ?: ''),
                        ]),
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 1,
                        'url' => "/warehouse/report/{$warehouseReportSerialNumber}",
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ]);

                # ???????????????????????? ??????
                $entireInstance->FillClearInstallPositionForIn()->saveOrFail();
                if (!empty($entireInstance->PartInstances)) {
                    $entireInstance->PartInstances->each(function (EntireInstance $part_instance) {
                        $part_instance->FillClearInstallPositionForIn()->saveOrfail();
                    });
                }
            });
            DB::commit();
            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param string $serial_number
     * @return mixed
     * @throws EmptyException
     * @throws Throwable
     * @throws ValidateException
     */
    final public function PostSignImg(Request $request)
    {
        $data = $request->get("data");
        if (!$data) return JsonResponseFacade::errorValidate("???????????????");
        $data = json_decode($data, true);

        $serial_number = @$data["unique_code"] ?: "";
        if (!$serial_number) return JsonResponseFacade::errorValidate("?????????????????????");

        $type = @$data["type"] ?: "";
        if (!$type) return JsonResponseFacade::errorValidate("?????????????????????");

        $sign_image = @$request->get("sign", "") ?: "";
        if (!$sign_image) return JsonResponseFacade::errorValidate("??????????????????");

        switch ($type) {
            case 'WAREHOUSE':
                $warehouse_report = WarehouseReport::with([])->where("serial_number", $serial_number)->first();
                if (!$warehouse_report) return JsonResponseFacade::errorEmpty("?????????????????????");
                $warehouse_report->fill(["sign_image" => $sign_image,])->saveOrFail();
                return JsonResponseFacade::ok("????????????1");
            case "SEND_REPAIR":
                $send_repair = SendRepair::with([])->where("unique_code", $serial_number)->first();
                if (!$send_repair) return JsonResponseFacade::errorEmpty("??????????????????");
                $send_repair->fill(["sign_image" => $sign_image,])->saveOrFail();
                return JsonResponseFacade::ok("????????????2");
        }
        // return JsonResponseFacade::ok("????????????");
    }

    /**
     * ??????->????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopOutOfSearch(Request $request): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([])
                ->select(['identity_code', 'category_name', 'model_name as sub_model_name', 'status'])
                ->where('identity_code', $request->get('data'))
                ->firstOrFail();

            return JsonResponseFacade::data($entire_instance);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('????????????????????????');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopOut(Request $request): JsonResponse
    {
        try {
            $identity_codes = $request->get('datas');
            if (empty($identity_codes)) return JsonResponseFacade::errorEmpty('??????????????????????????????');
            $date = @$request->get('date', '') ?: '';
            try {
                $date = Carbon::parse($date);
            } catch (Exception $e) {
                return JsonResponseFacade::errorForbidden('????????????????????????');
            }

            $sign_image = $request->get('sign', '') ?: '';
            // if (!$sign_img) return JsonResponseFacade::errorValidate('????????????');

            $entire_instances = EntireInstance::with([])->whereIn('identity_code', $identity_codes)->get();

            $check_out_use = WarehouseReportFacade::checkOutUse(
                $request->get('station_unique_code', '') ?? '',
                $request->get('scene_workshop_unique_code', '') ?? '',
                $request->get('line_unique_code', '') ?? ''
            );
            if (!$check_out_use['ret']) return JsonResponseFacade::errorValidate($check_out_use['msg']);

            [
                'station' => $station,
                'scene_workshop' => $scene_workshop,
                'line' => $line,
                'use_name' => $use_name,
            ] = $check_out_use;
            $standard_batch_out = WarehouseReportFacade::standardBatchOut(
                $entire_instances,
                $date->format('Y-m-d H:i:s'),
                $request->get('connection_name', '') ?? '',
                $request->get('connection_phone', '') ?? '',
                $station,
                $scene_workshop,
                $line,
                $use_name,
                $sign_image
            );
            if (!$standard_batch_out['ret'])
                return JsonResponseFacade::errorForbidden($standard_batch_out['msg']);

            // return JsonResponseFacade::created(['warehouse_report_sn' => $standard_batch_out['warehouse_report_sn']], '????????????');


            // $scene_workshop = null;
            // if ($request->get('scene_workshop_unique_code')) $scene_workshop = Maintain::with([])->where('unique_code', $request->get('scene_workshop_unique_code'))->where('type', 'SCENE_WORKSHOP')->where('parent_unique_code', env('ORGANIZATION_CODE'))->first();
            // $station = null;
            // if ($request->get('station_unique_code')) $station = Maintain::with([])->where('unique_code', $request->get('station_unique_code'))->where('type', 'STATION')->first();
            // if ($station) {
            //     if (!$station->Parent) return JsonResponseFacade::errorValidate('??????????????????????????????????????????????????????');
            //     if ($scene_workshop) {
            //         if ($scene_workshop->name != $station->Parent->name) return JsonResponseFacade::errorValidate('??????????????????????????????????????????');
            //     }
            // }
            // $line = null;
            // if ($request->get('line_unique_code')) $line = Line::with([])->where('unique_code', $request->get('line_unique_code'))->first();
            // if (!$line && !$scene_workshop && $station) return JsonResponseFacade::errorValidate('??????????????????????????????????????????');
            //
            // $entireInstances = EntireInstance::with([
            //     'Station',
            //     'Station.Parent',
            //     'EntireModel',
            //     'EntireMode.Parent',
            // ])
            //     ->whereIn('identity_code', $identity_codes)
            //     ->get();
            //
            // DB::beginTransaction();
            // $warehouse_report = WarehouseReport::with([])->create([
            //     'created_at' => $date,
            //     'updated_at' => $date,
            //     'processor_id' => $account_id,
            //     'processed_at' => $date,
            //     'connection_name' => $contact_name,
            //     'connection_phone' => $contact_phone,
            //     'scene_workshop_name' => @$station ? $station->Parent->name : (@$scene_workshop->name ?: ''),  // ????????????,
            //     'station_name' => @$station->name ?: '',  // ????????????,
            //     'scene_workshop_unique_code' => @$station ? $station->Parent->unique_code : (@$scene_workshop->unique_code ?: ''),  // ????????????,
            //     'maintain_station_unique_code' => @$station->unique_code ?: '',  // ????????????
            //     'type' => 'INSTALL',
            //     'direction' => 'OUT',
            //     'serial_number' => $warehouseReportSerialNumber = CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
            //     'work_area_id' => $work_area_id,
            //     'work_area_unique_code' => session('account.work_area_unique_code'),
            //     'sign_image' => $sign_image,
            // ]);
            // if ($sign_image) $warehouse_report->saveSignImage($sign_image);
            //
            // $entireInstances->each(function ($entireInstance)
            // use ($request, $date, $account_nickname, $contact_name, $contact_phone, $warehouseReportSerialNumber, $station, $scene_workshop) {
            //     $can_i_warehouse_out = $entireInstance->can_i_warehouse_in;
            //     if ($can_i_warehouse_out !== true) return JsonResponseFacade::errorForbidden($can_i_warehouse_out);
            //
            //     // ???????????????????????????
            //     $fix_cycle_value = @$entireInstance->EntireModel->fix_cycle_value ?: (@$entireInstance->EntireModel->Parent->fix_cycle_value ?: 0);
            //     $next_fixing_time = null;
            //     if ($fix_cycle_value > 0) {
            //         $next_fixing_time = $date->copy()->addYear($fix_cycle_value);
            //     }
            //
            //     # ????????????????????????
            //     $entireInstance->fill([
            //         'updated_at' => $date->format('Y-m-d'),  // ????????????
            //         'last_out_at' => $date->format('Y-m-d'),  // ????????????
            //         'in_warehouse_breakdown_explain' => '',
            //         'last_warehouse_report_serial_number_by_out' => $warehouseReportSerialNumber,
            //         'location_unique_code' => '',
            //         'is_bind_location' => 0,
            //         'is_overhaul' => '0',
            //         'maintain_station_name' => @$station->name ?: '',  // ????????????
            //         'maintain_workshop_name' => @$station ? $station->Parent->name : (@$scene_workshop->name ?: ''),  // ????????????
            //         'status' => 'TRANSFER_OUT',  // ?????????????????????
            //         'next_fixing_time' => @$next_fixing_time ? $next_fixing_time->timestamp : null,  // ????????????????????????
            //         'next_fixing_month' => @$next_fixing_time ? $next_fixing_time->format('Y-m-01') : null,  // ?????????????????????
            //         'next_fixing_day' => @$next_fixing_time ? $next_fixing_time->format('Y-m-d') : null,  // ?????????????????????
            //     ])
            //         ->saveOrFail();
            //
            //     # ?????????????????????
            //     // EntireInstanceFacade::nextFixingTimeWithIdentityCode($entireInstance->identity_code);
            //
            //     # ??????????????????????????????
            //     DB::table('warehouse_report_entire_instances')->insert([
            //         'created_at' => $date,
            //         'updated_at' => $date,
            //         'warehouse_report_serial_number' => $warehouseReportSerialNumber,
            //         'entire_instance_identity_code' => $entireInstance->identity_code,
            //         'in_warehouse_breakdown_explain' => '',
            //         'maintain_station_name' => @$station->name ?: '',  // ????????????,
            //         'maintain_location_code' => @$station ? $station->Parent->name : (@$scene_workshop->name ?: ''),  // ????????????,
            //         'crossroad_number' => $entireInstance->crossroad_number,
            //         'traction' => $entireInstance->traction,
            //         'line_name' => $entireInstance->line_name,
            //         'crossroad_type' => $entireInstance->crossroad_type,
            //         'extrusion_protect' => $entireInstance->extrusion_protect,
            //         'point_switch_group_type' => $entireInstance->point_switch_group_type,
            //         'open_direction' => $entireInstance->open_direction,
            //         'said_rod' => $entireInstance->said_rod,
            //         'line_unique_code' => $entireInstance->line_unique_code,
            //     ]);
            //
            //     # ????????????????????????
            //     EntireInstanceLog::with([])
            //         ->create([
            //             'name' => '??????',
            //             'description' => implode('???', [
            //                 '????????????' . $account_nickname,
            //                 '????????????' . @$contact_name ?: '',
            //                 '???????????????' . @$contact_phone ?: '',
            //                 '?????????' . @$entireInstance->Station->Parent ? @$entireInstance->Station->Parent->name : '' . ' ' . @$entireInstance->Station->Parent->name ?? '',
            //                 '???????????????' . InstallPosition::getRealName(@$entireInstance->last_maintain_location_code ?: (@$entireInstance->maintain_location_code ?: ''), @$entireInstance->last_maintain_location_code ?: (@$entireInstance->maintain_location_code ?: ''))
            //                 . @$entireInstance->last_crossroad_number ?: (@$entireInstance->crossroad_number ?: '') . ' ' . @$entireInstance->last_open_direction ?: (@$entireInstance->open_direction ?: ''),
            //             ]),
            //             'entire_instance_identity_code' => $entireInstance->identity_code,
            //             'type' => 1,
            //             'url' => "/warehouse/report/{$warehouseReportSerialNumber}",
            //             'operator_id' => session('account.id'),
            //             'station_unique_code' => '',
            //         ]);
            // });
            // DB::commit();

            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
            // return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
            // return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????->??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postScrapOfSearch(Request $request): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([])
                ->select(['identity_code', 'category_name', 'model_name as sub_model_name', 'status'])
                ->where('identity_code', $request->get('data'))
                ->firstOrFail();
            return JsonResponseFacade::data($entire_instance);

            // $identityCode = $request->get('data');
            // $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            // if (empty($data)) return JsonResponseFacade::errorEmpty();
            // $data = [
            //     'identity_code' => $data[0]->identity_code,
            //     'category_name' => $data[0]->category_name,
            //     'sub_model_name' => $data[0]->model_name,
            //     'status' => $this->_statuses[$data[0]->status]
            // ];
            // return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
            // return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
            // return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse|string[]
     */
    final public function postScrap(Request $request)
    {
        try {
            $date = $request->get('date');
            $identityCodes = $request->get('datas');
            $entireIdentityCodes = DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();
            $partIdentityCodes = DB::table('part_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();

            DB::transaction(function () use ($entireIdentityCodes, $partIdentityCodes, $date) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('SCRAP');
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'state' => 'END',
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'SCRAP',
                    'account_id' => session('account.id')
                ]);
                $warehouseMaterials = [];
                $entireInstanceLogs = [];
                foreach ($entireIdentityCodes as $entireIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $entireIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'ENTIRE'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'name' => '??????',
                        'description' => '????????????' . session('account.nickname') . '???',
                        'entire_instance_identity_code' => $entireIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ];
                }
                foreach ($partIdentityCodes as $partIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $partIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'PART'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'name' => '??????',
                        'description' => '????????????' . session('account.nickname') . '???',
                        'entire_instance_identity_code' => $partIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'PART',
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ];
                }

                DB::table('warehouse_materials')->insert($warehouseMaterials);
                EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
                // ????????????????????????
                DB::table('entire_instances')->whereIn('identity_code', $entireIdentityCodes)->update([
                    'status' => 'SCRAP',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('part_instances')->whereIn('identity_code', $partIdentityCodes)->update([
                    'status' => 'SCRAP',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('warehouses')->where('direction', 'SCRAP')->where('account_id', session('account.id'))->where('unique_code', '<>', $warehouseUniqueCode)->where('state', 'START')->update([
                    'state' => 'CANCEL',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            });

            return JsonResponseFacade::created([], '????????????');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????->??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postFrmLossOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('data');
            $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            if (empty($data)) return JsonResponseFacade::errorEmpty();
            $data = [
                'identity_code' => $data[0]->identity_code,
                'category_name' => $data[0]->category_name,
                'sub_model_name' => $data[0]->model_name,
                'status' => $this->_statuses[$data[0]->status]
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse|string[]
     */
    final public function postFrmLoss(Request $request)
    {
        try {
            $date = $request->get('date');
            $identityCodes = $request->get('datas');
            $entireIdentityCodes = DB::table('entire_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();
            $partIdentityCodes = DB::table('part_instances')->where('deleted_at', null)->whereIn('identity_code', $identityCodes)->pluck('identity_code')->toArray();

            DB::transaction(function () use ($entireIdentityCodes, $partIdentityCodes, $date) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('FRMLOSS');
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'state' => 'END',
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'FRMLOSS',
                    'account_id' => session('account.id')
                ]);
                $warehouseMaterials = [];
                $entireInstanceLogs = [];
                foreach ($entireIdentityCodes as $entireIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $entireIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'ENTIRE'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'name' => '??????',
                        'description' => '????????????' . session('account.nickname') . '???',
                        'entire_instance_identity_code' => $entireIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ];
                }
                foreach ($partIdentityCodes as $partIdentityCode) {
                    $warehouseMaterials[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'material_unique_code' => $partIdentityCode,
                        'warehouse_unique_code' => $warehouseUniqueCode,
                        'material_type' => 'PART'
                    ];
                    $entireInstanceLogs[] = [
                        'created_at' => $date,
                        'updated_at' => $date,
                        'name' => '??????',
                        'description' => '????????????' . session('account.nickname') . '???',
                        'entire_instance_identity_code' => $partIdentityCode,
                        'type' => 0,
                        'url' => "/storehouse/index/{$warehouseId}",
                        'material_type' => 'PART',
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ];
                }

                DB::table('warehouse_materials')->insert($warehouseMaterials);
                EntireInstanceLogFacade::makeBatchUseArray($entireInstanceLogs);
                // ????????????????????????
                DB::table('entire_instances')->whereIn('identity_code', $entireIdentityCodes)->update([
                    'status' => 'FRMLOSS',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('part_instances')->whereIn('identity_code', $partIdentityCodes)->update([
                    'status' => 'FRMLOSS',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                DB::table('warehouses')->where('direction', 'FRMLOSS')->where('account_id', session('account.id'))->where('unique_code', '<>', $warehouseUniqueCode)->where('state', 'START')->update([
                    'state' => 'CANCEL',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            });

            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????->??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWarehouseInOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('identity_code');
            $location_unique_code = $request->get('location_unique_code');
            $data = DB::table('entire_instances')->where('identity_code', $identityCode)->get()->toArray();
            $positions = DB::table('positions')->where('unique_code', $location_unique_code)->get()->toArray();
            if (empty($data)) return JsonResponseFacade::errorEmpty('?????????????????????');
            if (empty($positions)) return JsonResponseFacade::errorEmpty('???????????????');
            $location = Position::with([])->where('unique_code', $location_unique_code)->firstOrFail();
            $data = [
                'identity_code' => $data[0]->identity_code,
                'category_name' => $data[0]->category_name,
                'sub_model_name' => $data[0]->model_name,
                'status' => $this->_statuses[$data[0]->status],
                'location_name' => $location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . ' ' . $location->WithTier->WithShelf->WithPlatoon->WithArea->name . ' ' . $location->WithTier->WithShelf->WithPlatoon->name . $location->WithTier->WithShelf->name . $location->WithTier->name . $location->name
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWarehouseIn(Request $request): JsonResponse
    {
        try {
            $materials = $request->get('datas');
            $date = $request->get('date');
            $success_count = 0;

            DB::transaction(function () use ($materials, $date, &$success_count) {
                $warehouse = new Warehouse();
                $warehouseUniqueCode = $warehouse->getUniqueCode('IN_WAREHOUSE');
                $warehouse->fill([
                    'unique_code' => $warehouseUniqueCode,
                    'direction' => 'IN_WAREHOUSE',
                    'account_id' => session('account.id'),
                    'state' => 'END'
                ]);
                $warehouse->save();
                $warehouseId = $warehouse->id;

                foreach ($materials as $material) {
                    $location = Position::with([])->where('unique_code', $material['location_unique_code'])->firstOrFail();
                    $areaType = @$location->WithTier->WithShelf->WithPlatoon->WithArea->type['value'] ?: 'FIXED';
                    $location = @$location->WithTier ? @$location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @$location->WithTier->WithShelf->WithPlatoon->name . @$location->WithTier->WithShelf->name . @$location->WithTier->name . @$location->name : '';
                    switch (DB::table('part_instances')->where('identity_code', $material['location_unique_code'])->exists()) {
                        case '0':
                            // ??????
                            $entire_instance = DB::table('entire_instances')->where('identity_code', $material['identity_code'])->select(['maintain_station_name', 'maintain_location_code', 'crossroad_number'])->first();
                            $description = '';
                            // $description .= "???????????????{$entire_instance->maintain_station_name} " . $entire_instance->maintain_location_code . $entire_instance->crossroad_number . "???";
                            $description .= "???????????????{$location}" . "???" . "????????????" . session('account.nickname') . "???";
                            EntireInstanceLogFacade::makeOne(
                                session('account.id'),
                                '',
                                '??????',
                                $material['identity_code'],
                                0,
                                "/storehouse/index/{$warehouseId}",
                                $description,
                                'ENTIRE'
                            );

                            DB::table('entire_instances')
                                ->where('identity_code', $material['identity_code'])
                                ->update([
                                    'location_unique_code' => $material['location_unique_code'],
                                    'is_bind_location' => 1,
                                    'in_warehouse_time' => $date,
                                    'updated_at' => $date,
                                    'maintain_station_name' => '',
                                    'maintain_workshop_name' => env('JWT_ISS'),
                                    'maintain_location_code' => '',
                                    'crossroad_number' => '',
                                ]);
                            DB::table('warehouse_materials')
                                ->insert([
                                    'created_at' => $date,
                                    'updated_at' => $date,
                                    'material_unique_code' => $material['identity_code'],
                                    'warehouse_unique_code' => $warehouseUniqueCode,
                                    'material_type' => 'ENTIRE'
                                ]);
                            break;
                        case '1':
                            // ??????
                            $description = "???????????????{$location}" . "???" . "????????????" . session('account.nickname') . '???';
                            EntireInstanceLogFacade::makeOne(
                                session('account.id'),
                                '',
                                '??????',
                                $material['identity_code'],
                                0,
                                "/storehouse/index/{$warehouseId}",
                                $description,
                                'PART'
                            );

                            DB::table('part_instances')->where('identity_code', $material['identity_code'])
                                ->update([
                                    'status' => $areaType,
                                    'location_unique_code' => $material['location_unique_code'],
                                    'is_bind_location' => 1,
                                    'in_warehouse_time' => $date,
                                    'updated_at' => $date
                                ]);
                            DB::table('warehouse_materials')->insert([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'material_unique_code' => $material['identity_code'],
                                'warehouse_unique_code' => $warehouseUniqueCode,
                                'material_type' => 'PART'
                            ]);
                            break;
                        default:
                            break;
                    }
                    $success_count++;
                }
            });
            return JsonResponseFacade::created([], "???????????????{$success_count}???");
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('?????????????????????');
            // return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
            // return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return mixed
     */
    final public function postInstall2(Request $request)
    {
        try {
            $date = $request->get('Y-m-d', date('Y-m-d'));
            $nickname = session('account.nickname');
            $new_entire_instance = EntireInstance::with([])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postInstall(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type');
            switch ($type) {
                case 'cycle':
                    // ???????????????
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();
                    $oldEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('old_identity_code'))->first();
                    if (
                        ($oldEntireInstance->maintain_location_code != $newEntireInstance->maintain_location_code) ||
                        ($oldEntireInstance->maintain_station_name != $newEntireInstance->maintain_station_name)
                    ) return JsonResponseFacade::errorEmpty('?????????????????????');
                    DB::transaction(function () use ($newEntireInstance, $oldEntireInstance, $date, $nickname) {
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '??????',
                                'description' => implode('???', [
                                    "{$oldEntireInstance->identity_code}???{$newEntireInstance->identity_code}??????",
                                    "?????????{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $oldEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$oldEntireInstance->Station->unique_code ?? '',
                            ]);
                        // ??????????????????
                        $oldEntireInstance->fill([
                            'status' => 'UNINSTALLED',  // ??????
                            'maintain_workshop_name' => env('JWT_ISS'),  // ????????????????????????????????????
                            'updated_at' => now(),  // ????????????
                            'maintain_station_name' => '',  // ????????????
                            'crossroad_number' => '',  // ?????????
                            'open_direction' => '',  // ??????
                            'maintain_location_code' => '',  // ??????????????????
                            'next_fixing_time' => null,  // ????????????????????????
                            'next_fixing_month' => null,  // ?????????????????????
                            'next_fixing_day' => null,  // ?????????????????????
                        ])
                            ->saveOrFail();

                        // ??????????????????
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'installed_at' => $date,
                            'source' => $oldEntireInstance->source,
                            'source_traction' => $oldEntireInstance->source_traction,
                            'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            'traction' => $oldEntireInstance->traction,
                            'open_direction' => $oldEntireInstance->open_direction,
                            'said_rod' => $oldEntireInstance->said_rod,
                            'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            'crossroad_number' => $oldEntireInstance->crossroad_number,
                            'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '???????????????',
                                'description' => implode('???', [
                                    "{$newEntireInstance->identity_code}??????{$oldEntireInstance->identity_code}",
                                    "?????????{$newEntireInstance->Station->Parent->name} {$newEntireInstance->maintain_station_name} {$newEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$newEntireInstance->Station->unique_code ?? '',
                            ]);
                    });
                    break;
                case 'emergency':
                    #????????????
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();
                    $oldEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('old_identity_code'))->first();

                    DB::transaction(function () use ($newEntireInstance, $oldEntireInstance, $date, $nickname) {
                        // ??????????????????
                        $oldEntireInstance->fill(['status' => 'UNINSTALLED'])->saveOrFail();
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '??????',
                                'description' => implode('???', [
                                    "{$oldEntireInstance->identity_code}???{$newEntireInstance->identity_code}??????",
                                    "?????????{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $oldEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$oldEntireInstance->Station->unique_code ?? '',
                            ]);

                        // ??????????????????
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'installed_at' => $date,
                            'source' => $oldEntireInstance->source,
                            'source_traction' => $oldEntireInstance->source_traction,
                            'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            'traction' => $oldEntireInstance->traction,
                            'open_direction' => $oldEntireInstance->open_direction,
                            'said_rod' => $oldEntireInstance->said_rod,
                            'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            'crossroad_number' => $oldEntireInstance->crossroad_number,
                            'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '????????????',
                                'description' => implode('???', [
                                    "{$newEntireInstance->identity_code}??????{$oldEntireInstance->identity_code}",
                                    "?????????{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$newEntireInstance->Station->unique_code ?? '',
                            ]);
                    });
                    break;
                case 'fault':
                    // ???????????????
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('new_identity_code'))->firstOrFail();
                    $oldEntireInstance = EntireInstance::with(['Station', 'Station.Parent'])->where('identity_code', $request->get('old_identity_code'))->first();
                    if (
                        ($oldEntireInstance->maintain_location_code != $newEntireInstance->maintain_location_code) ||
                        ($oldEntireInstance->maintain_station_name != $newEntireInstance->maintain_station_name)
                    ) return JsonResponseFacade::errorEmpty('?????????????????????');
                    DB::transaction(function () use ($newEntireInstance, $oldEntireInstance, $date, $nickname) {
                        // ??????????????????
                        $oldEntireInstance->fill(['status' => 'UNINSTALLED'])->saveOrFail();
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '??????',
                                'description' => implode('???', [
                                    "{$oldEntireInstance->identity_code}???{$newEntireInstance->identity_code}??????",
                                    "?????????{$oldEntireInstance->Station->Parent->name} {$oldEntireInstance->maintain_station_name} {$oldEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $oldEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$oldEntireInstance->Station->unique_code ?? '',
                            ]);

                        // ????????????
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'installed_at' => $date,
                            'source' => $oldEntireInstance->source,
                            'source_traction' => $oldEntireInstance->source_traction,
                            'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            'traction' => $oldEntireInstance->traction,
                            'open_direction' => $oldEntireInstance->open_direction,
                            'said_rod' => $oldEntireInstance->said_rod,
                            'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            'crossroad_number' => $oldEntireInstance->crossroad_number,
                            'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '???????????????',
                                'description' => implode('???', [
                                    "{$newEntireInstance->identity_code}??????{$oldEntireInstance->identity_code}",
                                    "?????????{$newEntireInstance->Station->Parent->name} {$newEntireInstance->maintain_station_name} {$newEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$newEntireInstance->Station->unique_code ?? '',
                            ]);
                    });
                    break;
                case 'direct':
                    // ????????????
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    $newEntireInstance = EntireInstance::with([
                        'Station',
                        'Station.Parent',
                    ])
                        ->where('identity_code', $request->get('new_identity_code'))
                        ->firstOrFail();

                    DB::transaction(function () use ($newEntireInstance, $date, $nickname) {
                        // ????????????
                        $newEntireInstance->fill([
                            'status' => 'INSTALLED',
                            'installed_at' => $date,
                            // 'source' => $oldEntireInstance->source,
                            // 'source_traction' => $oldEntireInstance->source_traction,
                            // 'source_crossroad_number' => $oldEntireInstance->source_crossroad_number,
                            // 'traction' => $oldEntireInstance->traction,
                            // 'open_direction' => $oldEntireInstance->open_direction,
                            // 'said_rod' => $oldEntireInstance->said_rod,
                            // 'maintain_location_code' => $oldEntireInstance->maintain_location_code,
                            // 'crossroad_number' => $oldEntireInstance->crossroad_number,
                            // 'maintain_station_name' => $oldEntireInstance->maintain_station_name,
                            // 'maintain_workshop_name' => $oldEntireInstance->maintain_workshop_name
                        ])->saveOrFail();
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '????????????',
                                'description' => implode('???', [
                                    "{$newEntireInstance->identity_code}",
                                    "?????????{$newEntireInstance->Station->Parent->name} {$newEntireInstance->maintain_station_name} {$newEntireInstance->maintain_location_code}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $newEntireInstance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$newEntireInstance->Station->unique_code ?? '',
                            ]);
                    });
                    break;
                default:
                    break;
            }
            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("???????????????????????????{$request->get('new_identity_code')}");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????->??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function getInstalled(Request $request): JsonResponse
    {
        try {
            $entire_instances = EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
            ])
                ->where('identity_code', $request->get('code'))
                ->orWhere('serial_number', $request->get('code'))
                ->get();

            foreach ($entire_instances as $entire_instance) {
                if ($entire_instance->can_i_installed !== true)
                    return JsonResponseFacade::errorForbidden("???????????????{$request->get('code')}" . $entire_instance->can_i_installed);
            }

            return JsonResponseFacade::data($entire_instances);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ???????????????????????????????????????????????????????????????
     * @param Request $request
     * @param string $entire_instance_identity_code
     * @return mixed
     * @throws ValidateException
     * @throws Throwable
     */
    final public function postInDoorInstalledStrictAndInstalling(Request $request, string $entire_instance_identity_code)
    {
        /**
         * @????????????????????????
         * ??????????????? POST inDoorInstalledStrictAndInstalling
         * ??????maintain_location_code???crossroad_number???open_direction???station_unique_code???status
         */
        if (!$request->get("status")) return JsonResponseFacade::errorValidate("??????????????????");
        if (!$request->get("maintain_location_code") && !$request->get("crossroad_number")) return JsonResponseFacade::errorValidate("????????????????????????");

        $date = $request->get("date", now()->format("Y-m-d H:i:s"));
        $station = null;

        if ($request->get("maintain_location_code")) {
            $install_position = InstallPosition::with([
                "EntireInstance",
                "WithInstallTier",
                "WithInstallTier.WithInstallShelf",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation.Parent",
            ])
                ->where("unique_code", $request->get("maintain_location_code"))
                ->first();
            if (!$install_position) return JsonResponseFacade::errorValidate("???????????????????????????" . $request->get("maintain_location_code"));
            $station = @$install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation;

            // ??????????????????
            // if ($install_position->EntireInstances->count() >= $install_position->volume) return JsonResponseFacade::errorForbidden("?????????????????????{$install_position->volume}?????????");
            if (!$station) return JsonResponseFacade::errorValidate("???????????????" . $request->get("maintain_location_code") . "????????????????????????");
            if (!@$station->Parent) return JsonResponseFacade::errorValidate("?????????{$station->name}??????????????????????????????");
        } else {
            return JsonResponseFacade::errorEmpty("?????????????????????");
        }

        if (CodeFacade::isIdentityCode($entire_instance_identity_code)) {
            $entire_instance = EntireInstance::with(["InstallPosition",])->where("identity_code", $entire_instance_identity_code)->first();
            if (!$entire_instance) return JsonResponseFacade::errorEmpty("?????????????????????");
        } else {
            $entire_instances = EntireInstance::with(["InstallPosition",])->where("serial_number", $entire_instance_identity_code)->get();
            if ($entire_instances->count() > 1) return JsonResponseFacade::errorEmpty("??????????????????????????????");
            $entire_instance = $entire_instances->first();
        }

        switch (strtoupper($request->get("status"))) {
            case "INSTALLED":
                // ??????????????????????????????
                // ???????????????????????????????????????????????????
                $can_i_installed = $entire_instance->can_i_installed;
                // ????????????????????????
                $install_position = null;
                if ($install_position) if ($install_position->EntireInstances->count() >= $install_position->volume) return JsonResponseFacade::errorForbidden("?????????????????????{$install_position->volume}?????????");
                if ($can_i_installed !== true) return JsonResponseFacade::errorForbidden($can_i_installed);

                if (array_flip(EntireInstance::$STATUSES)[$entire_instance->status] == "INSTALLING") {
                    EntireInstanceLog::with([])
                        ->create([
                            "created_at" => $date,
                            "updated_at" => $date,
                            "name" => "????????????",
                            "description" => implode("???", [
                                "????????????" . session("account.nickname") ?? "???",
                                "???????????????" . @$station->Parent->name ?: "???",
                                "?????????" . @$station->name ?: "???",
                                "?????????" . @$install_position->real_name ?: "???",
                            ]),
                            "entire_instance_identity_code" => $entire_instance->identity_code,
                            "type" => 4,
                            "url" => "",
                            "operator_id" => session("account.id"),
                            "station_unique_code" => @$station->unique_code ?? "",
                        ]);
                }

                // ??????
                $entire_instance
                    ->fill([
                        "maintain_station_name" => @$station->name ?: "",
                        "maintain_workshop_name" => @$station->Parent->name ?: "",
                        "maintain_location_code" => $request->get("maintain_location_code") ?? "",
                        "crossroad_number" => $request->get("crossroad_number") ?? "",
                        "open_direction" => $request->get("open_direction") ?? "",
                        "installed_at" => now(),
                        "is_emergency" => false,
                        "status" => $request->get("status"),
                    ]);
                if ($request->get("serial_number")) {
                    if (DB::table("entire_instances")->whereNull("deleted_at")->where("identity_code", "<>", $entire_instance_identity_code)->where("serial_number", $request->get("serial_number"))->exists()) {
                        return JsonResponseFacade::errorValidate("??????????????????????????????");
                    }
                    $entire_instance->fill([
                        "serial_number" => $request->get("serial_number"),
                    ]);
                }
                $entire_instance->saveOrFail();


                // ????????????
                EntireInstanceLog::with([])
                    ->create([
                        "name" => "??????",
                        "description" => implode("???", [
                            "???????????????" . $station->Parent->name,
                            "?????????" . $station->name,
                            "?????????" . @$install_position->real_name ?: "???",
                            "????????????" . session("account.nickname"),
                        ]),
                        "entire_instance_identity_code" => $entire_instance->identity_code,
                        "type" => 4,
                        "url" => "",
                        "operator_id" => session("account.id"),
                        "station_unique_code" => @$station->unique_code ?? "",
                    ]);

                // ??????????????????
                EntireInstanceUseReport::with([])->create([
                    "id" => EntireInstanceUseReport::generateId(),
                    "entire_instance_identity_code" => $entire_instance->identity_code,
                    "scene_workshop_unique_code" => @$station->Parent->unique_code ?? "",
                    "maintain_station_unique_code" => @$station->unique_code ?? "",
                    "maintain_location_code" => @$install_position->real_name ?: "???",
                    "processor_id" => session("account.id"),
                    "crossroad_number" => $request->get("crossroad_number") ?? "",
                    "open_direction" => $request->get("open_direction") ?? "",
                    "type" => "INSTALLED",
                    "status" => "DONE",
                ]);

                return JsonResponseFacade::created(["entire_instance" => $entire_instance], "????????????");
            case "INSTALLING":
                // ????????????
                // ????????????
                $can_i_installing = $entire_instance->can_i_installing;
                if ($can_i_installing !== true) return JsonResponseFacade::errorForbidden($can_i_installing);

                EntireInstanceLog::with([])
                    ->create([
                        "created_at" => $date,
                        "updated_at" => $date,
                        "name" => "??????????????????",
                        "description" => implode("???", [
                            "????????????" . session("account.nickname") ?? "???",
                            "???????????????" . @$station->Parent->name ?: "???",
                            "?????????" . @$station->name ?: "???",
                            "?????????" . @$install_position->real_name ?: "???",
                        ]),
                        "entire_instance_identity_code" => $entire_instance->identity_code,
                        "type" => 4,
                        "url" => "",
                        "operator_id" => session("account.id"),
                        "station_unique_code" => @$station->unique_code ?? "",
                    ]);

                // ??????????????????
                $entire_instance->fill([
                    "status" => "INSTALLING",
                    "maintain_workshop_name" => @$station->Parent->name ?: "",
                    "maintain_station_name" => @$station->name ?: "",
                    "maintain_location_code" => $request->get("maintain_location_code") ?? "",
                    "crossroad_number" => "",
                    "open_direction" => "",
                    "installed_at" => now(),
                    "is_emergency" => $request->get("is_emergency", false) ?? false,
                ]);
                if ($request->get("serial_number")) {
                    if (DB::table("entire_instances")->whereNull("deleted_at")->where("identity_code", "<>", $entire_instance_identity_code)->where("serial_number", $request->get("serial_number"))->exists()) {
                        return JsonResponseFacade::errorValidate("??????????????????????????????");
                    }
                    $entire_instance->fill([
                        "serial_number" => $request->get("serial_number"),
                    ]);
                }
                $entire_instance->saveOrFail();

                // ??????????????????
                EntireInstanceUseReport::with([])->create([
                    "id" => EntireInstanceUseReport::generateId(),
                    "entire_instance_identity_code" => $entire_instance->identity_code,
                    "scene_workshop_unique_code" => @$entire_instance->Station->Parent->unique_code ?? "",
                    "maintain_station_unique_code" => @$entire_instance->Station->unique_code ?? "",
                    "maintain_location_code" => @$entire_instance->maintain_location_code ?: "",
                    "processor_id" => session("account.id"),
                    "crossroad_number" => "",
                    "open_direction" => "",
                    "type" => "INSTALLING",
                    "status" => "DONE",
                ]);

                return JsonResponseFacade::created(["entire_instance" => $entire_instance], "????????????");
            default:
                return JsonResponseFacade::errorValidate("??????????????????");
        }
    }

    /**
     * ????????????????????????????????????????????????
     * @throws Throwable
     */
    final public function postOutDoorInstalledUnStrictAndInDoorInstalledStrict(Request $request, string $entire_instance_identity_code)
    {
        // return JsonResponseFacade::dump($request->all());

        // if (!$request->get("maintain_location_code") && !$request->get("crossroad_number")) return JsonResponseFacade::errorValidate("????????????????????????");

        // if (empty($request->get("maintain_location_code"))) {
        //     $installed_entire_instance = EntireInstance::with([])->where("maintain_location_code", $request->get("maintain_location_code"))->first();
        //     if ($installed_entire_instance) return JsonResponseFacade::errorForbidden("????????????????????????");
        // }

        if (
            !$request->get("maintain_location_code")
            && !$request->get("crossroad_number")
            && !$request->get("open_direction")
            && !$request->get("maintain_section_name")
            && !$request->get("maintain_send_or_receive")
            && !$request->get("maintain_signal_post_main_or_indicator_code")
            && !$request->get("maintain_signal_post_main_light_position_code")
            && !$request->get("maintain_signal_post_indicator_light_position_code")
        ) return JsonResponseFacade::errorEmpty("??????????????????");

        $station = null;
        if ($request->get("station_unique_code")) {
            $station = Maintain::with(["Parent"])->where("unique_code", $request->get("station_unique_code"))->first();
            if (!$station) return JsonResponseFacade::errorForbidden("???????????????");
            if (!@$station->Parent) return JsonResponseFacade::errorForbidden("???????????????????????????????????????????????????????????????");
        }

        $date = $request->get("date", now()->format("Y-m-d H:i:s"));

        $entire_instance = EntireInstance::with(["InstallPosition", "PartInstances",])->where("identity_code", $entire_instance_identity_code)->firstOrFail();

        // $install_position = InstallPosition::with([])->where("unique_code", $request->get("maintain_location_code", "") ?? "")->first();

        // ???????????????????????????????????????????????????
        if ($entire_instance->property("status") == "INSTALLING") {
            // if (array_flip(EntireInstance::$STATUSES)[$entire_instance->status] == "INSTALLING") {
            EntireInstanceLog::with([])
                ->create([
                    "created_at" => $date,
                    "updated_at" => $date,
                    "name" => "????????????",
                    "description" => TextFacade::joinWithNotEmpty("???", [
                        $entire_instance->use_position_name ? "???????????????{$entire_instance->use_position_name}" : "",
                        "????????????" . session("account.nickname") ?? "???",
                    ]),
                    "entire_instance_identity_code" => $entire_instance->identity_code,
                    "type" => 4,
                    "url" => "",
                    "operator_id" => session("account.id"),
                    "station_unique_code" => @$station->unique_code ?? "",
                ]);
        }

        $update_datum = [
            "maintain_station_name" => @$station->name ?: "",
            "maintain_workshop_name" => @$station->Parent->name ?: "",
            "maintain_location_code" => $request->get("maintain_location_code") ?? "",
            "crossroad_number" => $request->get("crossroad_number") ?? "",
            "open_direction" => $request->get("open_direction") ?? "",
            "maintain_section_name" => $request->get("maintain_section_name") ?? "",
            "maintain_send_or_receive" => $request->get("maintain_send_or_receive") ?? "",
            "maintain_signal_post_main_or_indicator_code" => $request->get("maintain_signal_post_main_or_indicator_code") ?? "",
            "maintain_signal_post_main_light_position_code" => $request->get("maintain_signal_post_main_light_position_code") ?? "",
            "maintain_signal_post_indicator_light_position_code" => $request->get("maintain_signal_post_indicator_light_position_code") ?? "",
            "installed_at" => now(),
            "is_emergency" => false,
            "status" => "INSTALLED",
        ];

        // ?????? ??????
        $entire_instance->fill($update_datum)->saveOrFail();

        // ?????? ??????
        if (!empty($entire_instance->PartInstances)) {
            $entire_instance->PartInstances->each(function ($part_instance) use ($update_datum) {
                $part_instance->fill($update_datum)->saveOrFail();
            });
        }

        // ????????????
        EntireInstanceLog::with([])
            ->create([
                "name" => "??????",
                "description" => TextFacade::joinWithNotEmpty("???", [
                    $entire_instance->use_position_name ? "???????????????{$entire_instance->use_position_name}" : "",

                    "????????????" . session("account.nickname"),
                ]),
                "entire_instance_identity_code" => $entire_instance->identity_code,
                "type" => 4,
                "url" => "",
                "operator_id" => session("account.id"),
                "station_unique_code" => @$station->unique_code ?? "",
            ]);

        // ??????????????????
        EntireInstanceUseReport::with([])
            ->create([
                // "id" => EntireInstanceUseReport::generateId(),
                "id" => Str::uuid(),
                "entire_instance_identity_code" => $entire_instance->identity_code,
                "scene_workshop_unique_code" => @$station->Parent->unique_code ?? "",
                "maintain_station_unique_code" => @$station->unique_code ?? "",
                "maintain_location_code" => $request->get("maintain_location_code") ?? "",
                "processor_id" => session("account.id"),
                "crossroad_number" => $request->get("crossroad_number") ?? "",
                "open_direction" => $request->get("open_direction") ?? "",
                "maintain_section_name" => $request->get("maintain_section_name") ?? "",
                "maintain_send_or_receive" => $request->get("maintain_send_or_receive") ?? "",
                "maintain_signal_post_main_or_indicator_code" => $request->get("maintain_signal_post_main_or_indicator_code") ?? "",
                "maintain_signal_post_main_light_position_code" => $request->get("maintain_signal_post_main_light_position_code") ?? "",
                "maintain_signal_post_indicator_light_position_code" => $request->get("maintain_signal_post_indicator_light_position_code") ?? "",
                "type" => "INSTALLED",
                "status" => "DONE",
            ]);

        return JsonResponseFacade::created(["entire_instance" => $entire_instance], "????????????");
    }

    /**
     * ??????->??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function getUnInstall(Request $request): JsonResponse
    {
        try {
            $entire_instances = EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
            ])
                ->where('identity_code', $request->get('code'))
                ->orWhere('serial_number', $request->get('code'))
                ->get();

            foreach ($entire_instances as $entire_instance) {
                if ($entire_instance->can_i_uninstall !== true)
                    return JsonResponseFacade::errorForbidden("???????????????{$request->get('code')}" . $entire_instance->can_i_uninstall);
            }

            return JsonResponseFacade::data($entire_instances);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postUnInstall(Request $request): JsonResponse
    {
        try {
            /**
             * @todo ????????????????????????
             * 1. datas???????????????identity_codes
             * 2. ?????????????????????????????????
             */
            $date = $request->get("date", now());
            $count = 0;

            EntireInstance::with([
                "Station",
                "Station.Parent",
                "InstallPosition",
            ])
                ->whereIn("identity_code", $request->get("identity_codes"))
                ->chunk(50, function (Collection $entire_instances) use ($request, $date, &$count) {
                    $entire_instances->each(function (EntireInstance $entire_instance) use ($request, $date, &$count) {
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                "created_at" => $date,
                                "updated_at" => $date,
                                "name" => "??????",
                                "description" => implode("???", [
                                    "?????????" . $entire_instance->use_position_name,
                                    "????????????" . session("account.nickname"),
                                ]),
                                "entire_instance_identity_code" => $entire_instance->identity_code,
                                "type" => 4,
                                "url" => "",
                                "operator_id" => session("account.id"),
                                "station_unique_code" => @$entire_instance->Station->unique_code ?? "",
                            ]);

                        // ????????????????????????????????????????????????
                        $entire_instance->FillInheritInstallPositionForUnInstall(["status" => "TRANSFER_IN",])->saveOrFail();
                        // ????????????????????????????????????????????????
                        if (!empty($entire_instance->PartInstances)) {
                            $entire_instance->PartInstances->each(function (EntireInstance $part_instance) {
                                $part_instance->FillInheritInstallPositionForUnInstall(["status" => "TRANSFER_IN",])->saveOrFail();
                            });
                        }

                        // ??????????????????
                        EntireInstanceUseReport::with([])->create([
                            "id" => EntireInstanceUseReport::generateId(),
                            "entire_instance_identity_code" => $entire_instance->identity_code,
                            "scene_workshop_unique_code" => @$entire_instance->Station->Parent->unique_code ?? "",
                            "maintain_station_unique_code" => @$entire_instance->Station->unique_code ?? "",
                            "maintain_location_code" => $entire_instance->maintain_location_code ?: "",
                            "processor_id" => session("account.id"),
                            "crossroad_number" => $entire_instance->crossroad_number ?: "",
                            "open_direction" => $entire_instance->open_direction ?: "",
                            "maintain_section_name" => $entire_instance->maintain_section_name ?: "",
                            "maintain_send_or_receive" => $entire_instance->maintain_send_or_receive ?: "",
                            "maintain_signal_post_main_or_indicator_code" => $entire_instance->maintain_signal_post_main_or_indicator_code ?: "",
                            "maintain_signal_post_main_light_position_code" => $entire_instance->maintain_signal_post_main_light_position_code ?: "",
                            "maintain_signal_post_indicator_light_position_code" => $entire_instance->maintain_signal_post_indicator_light_position_code ?: "",
                            "type" => "UNINSTALL",
                            "status" => "DONE",
                        ]);

                        $count++;
                    });
                });

            return JsonResponseFacade::created([], "{$count}???????????????????????????");
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("???????????????????????????{$request->get("identity_code")}");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????->??????????????????(?????????/????????????)
     * @param Request $request
     * @return JsonResponse
     */
    final public function getInstalling(Request $request): JsonResponse
    {
        /**
         * @todo ??????????????????????????????
         * ??????????????????installingOfSearch
         * ?????????????????????entire_instances?????????????????????
         */
        try {
            $entire_instances = EntireInstance::with([
                'Station',
                'Station.Parent',
                'Category',
                'EntireModel',
                'SubModel',
            ])
                ->where('identity_code', $request->get('code'))
                ->orWhere('serial_number', $request->get('code'))
                ->get();

            foreach ($entire_instances as $entire_instance) {
                if ($entire_instance->can_i_installing !== true)
                    return JsonResponseFacade::errorForbidden("???????????????{$request->get('code')}" . $entire_instance->can_i_installing);
            }

            return JsonResponseFacade::data(['entire_instances' => $entire_instances->isNotEmpty() ? $entire_instances->toArray() : []]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postInstalling(Request $request): JsonResponse
    {
        try {
            /**
             * @todo ????????????????????????
             * ??????????????????installing
             * ???????????????station_unique_code???maintain_location_code
             */
            $date = $request->get('date', now());
            $count = 0;

            if (!$request->get('scene_workshop_unique_code') && !$request->get('station_unique_code'))
                return JsonResponseFacade::errorValidate('??????????????????????????????');

            $scene_workshop = null;
            if ($request->get('scene_workshop_unique_code')) {
                $scene_workshop = Maintain::with([])->where('unique_code', $request->get('scene_workshop_unique_code'))->first();
                if (!$scene_workshop) return JsonResponseFacade::errorForbidden('???????????????????????????');
            }

            $station = null;
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->first();
                if (!$station) return JsonResponseFacade::errorValidate('?????????????????????');
                if (!$station->Parent) return JsonResponseFacade::errorValidate('??????????????????????????????????????????????????????');
                $scene_workshop = $station->Parent;
            }

            EntireInstance::with([
                'Station',
                'Station.Parent',
                'InstallPosition',
            ])
                ->whereIn('identity_code', array_keys($request->get('entire_instances')))
                ->chunk(50, function (Collection $entire_instances) use ($request, $date, &$count, $scene_workshop, $station) {
                    $entire_instances->each(function (EntireInstance $entire_instance) use ($request, $date, &$count, $scene_workshop, $station) {
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '??????????????????',
                                'description' => implode('???', [
                                    '???????????????' . $scene_workshop ? $scene_workshop->name : '???',
                                    '?????????' . $station ? $station->name : '???',
                                    '?????????' . $request->get('entire_instances')[$entire_instance->identity_code] ?? '???',
                                    '????????????' . session('account.nickname'),
                                ]),
                                'entire_instance_identity_code' => $entire_instance->identity_code,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => @$station->unique_code ?? '',
                            ]);

                        // ??????????????????
                        $entire_instance->fill([
                            'status' => 'INSTALLING',
                            'maintain_workshop_name' => $scene_workshop ? $scene_workshop->name : '',
                            'maintain_station_name' => $station ? $station->name : '',
                            'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                            'crossroad_number' => '',
                            'open_direction' => '',
                            'installed_at' => now(),
                            'is_emergency' => $request->get('is_emergency', false) ?? false,
                        ])
                            ->saveOrFail();

                        // ??????????????????
                        EntireInstanceUseReport::with([])->create([
                            'id' => EntireInstanceUseReport::generateId(),
                            'entire_instance_identity_code' => $entire_instance->identity_code,
                            'scene_workshop_unique_code' => @$entire_instance->Station->Parent->unique_code ?? '',
                            'maintain_station_unique_code' => @$entire_instance->Station->unique_code ?? '',
                            'maintain_location_code' => @$entire_instance->maintain_location_code ?: '',
                            'processor_id' => session('account.id'),
                            'crossroad_number' => '',
                            'open_direction' => '',
                            'type' => 'INSTALLING',
                            'status' => 'DONE',
                        ]);

                        $count++;
                    });
                });

            return JsonResponseFacade::created([], "{$count}???????????????????????????");
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????(????????????)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSceneWarehouseIn(Request $request): JsonResponse
    {
        try {
            $materials = $request->get('datas');
            $date = $request->get('date');
            DB::transaction(function () use ($materials, $date) {
                foreach ($materials as $material) {
                    switch (DB::table('part_instances')->where('identity_code', $material['location_unique_code'])->exists()) {
                        case '0':
                            // ??????
                            $description = '';
                            $description .= "??????????????????" . "???" . "????????????" . session('account.nickname') . "???";
                            EntireInstanceLogFacade::makeOne(
                                session('account.id'),
                                '',
                                '??????',
                                $material['identity_code'],
                                0,
                                "",
                                $description,
                                'ENTIRE'
                            );

                            DB::table('entire_instances')->where('identity_code', $material['identity_code'])->update([
                                'status' => 'INSTALLING',
                                'is_bind_location' => 1,
                                'in_warehouse_time' => $date,
                                'updated_at' => $date,
                                'maintain_location_code' => '',
                                'crossroad_number' => '',
                                'source' => '',
                                'source_traction' => '',
                                'source_crossroad_number' => '',
                                'traction' => '',
                                'open_direction' => '',
                                'said_rod' => ''
                            ]);
                            break;
                        case '1':
                            // ??????
                            $description = '';
                            $description .= "??????????????????" . "???" . "????????????" . session('account.nickname') . "???";
                            EntireInstanceLogFacade::makeOne(
                                session('account.id'),
                                '',
                                '??????',
                                $material['identity_code'],
                                0,
                                "",
                                $description,
                                'PART'
                            );

                            DB::table('part_instances')->where('identity_code', $material['identity_code'])
                                ->update([
                                    'status' => 'INSTALLING',
                                    'is_bind_location' => 1,
                                    'in_warehouse_time' => $date,
                                    'updated_at' => $date,
                                    'maintain_location_code' => '',
                                    'crossroad_number' => '',
                                    'source' => '',
                                    'source_traction' => '',
                                    'source_crossroad_number' => '',
                                    'traction' => '',
                                    'open_direction' => '',
                                    'said_rod' => ''
                                ]);
                            break;
                        default:
                            break;
                    }
                }
            });
            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("???????????????????????????{$request->get('datas')}");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????????????????????????????
     * @return JsonResponse
     */
    final public function takeStockReady()
    {
        try {
            $locationUniqueCode = request('location_unique_code', '');
            if (empty($locationUniqueCode)) return JsonResponseFacade::errorEmpty('??????????????????????????????');
            $locationReturn = $this->_getLocationUniqueCode($locationUniqueCode);
            if ($locationReturn['status'] != 200) return JsonResponseFacade::errorEmpty($locationReturn['message']);

            // $entireInstances = EntireInstance::with(['WithPosition', 'EntireModel'])
            //     ->when(
            //         request("location_unique_code"),
            //         function ($query, $location_unique_code) {
            //             $query->where("location_unique_code", "like", "{$location_unique_code}%");
            //         }
            //     )
            //     ->get()
            //     ->map(function ($entireInstance) {
            //         $tmp = [
            //             'identity_code' => $entireInstance->identity_code,
            //             'category_name' => $entireInstance->category_name ?? '',
            //             'sub_model_name' => $entireInstance->model_name ?? '',
            //             'status' => $entireInstance->status ?? '',
            //             'material_type' => 'ENTIRE',
            //             'material_type_name' => '??????',
            //         ];
            //         return $tmp;
            //     });

            $storehouse_unique_code = $locationReturn['data']['storehouse_unique_code'];
            $area_unique_code = $locationReturn['data']['area_unique_code'];
            $platoon_unique_code = $locationReturn['data']['platoon_unique_code'];
            $shelf_unique_code = $locationReturn['data']['shelf_unique_code'];
            $tier_unique_code = $locationReturn['data']['tier_unique_code'];
            $position_unique_code = $locationReturn['data']['position_unique_code'];
            $data = [];
            // ??????
            $entireInstances = EntireInstance::with(['WithPosition', 'EntireModel'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            $partInstances = PartInstance::with(['WithPosition', 'PartCategory'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            if ($entireInstances->isEmpty() && $partInstances->isEmpty()) return JsonResponseFacade::errorEmpty('???????????????????????????');
            foreach ($entireInstances as $entireInstance) {
                $data[] = [
                    'identity_code' => $entireInstance->identity_code,
                    'category_name' => $entireInstance->category_name ?? '',
                    'sub_model_name' => $entireInstance->model_name ?? '',
                    'status' => $entireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '??????',
                ];
            }
            foreach ($partInstances as $partInstance) {
                $data[] = [
                    'identity_code' => $partInstance->identity_code,
                    'category_name' => $partInstance->PartCategory->name ?? '',
                    'sub_model_name' => $partInstance->part_model_name ?? '',
                    'status' => $partInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '??????',
                ];
            }
            return JsonResponseFacade::data($entireInstances);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????????????????
     * @param string $locationUniqueCode
     * @return array|JsonResponse
     */
    final private function _getLocationUniqueCode(string $locationUniqueCode)
    {
        $organizationLocationCode = env('ORGANIZATION_LOCATION_CODE');
        $lenOrganizationLocationCode = strlen($organizationLocationCode);
        if (substr($locationUniqueCode, 0, $lenOrganizationLocationCode) != $organizationLocationCode) return ['status' => 404, 'message' => '?????????????????????????????????'];
        $storehouse_unique_code = '';
        $area_unique_code = '';
        $platoon_unique_code = '';
        $shelf_unique_code = '';
        $tier_unique_code = '';
        $position_unique_code = '';
        $name = '';
        switch (strlen($locationUniqueCode)) {
            case $lenOrganizationLocationCode + 2:
                $storehouse = Storehouse::with([])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($storehouse)) {
                    $storehouse_unique_code = $storehouse->unique_code;
                    $name = $storehouse->name;
                }
                break;
            case $lenOrganizationLocationCode + 4:
                $area = Area::with(['WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($area)) {
                    $storehouse_unique_code = $area->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $area->unique_code;
                    $name = @$area->WithStorehouse->name . $area->name;
                }
                break;
            case $lenOrganizationLocationCode + 6:
                $platoon = Platoon::with(['WithArea', 'WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($platoon)) {
                    $storehouse_unique_code = $platoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $platoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $platoon->unique_code;
                    $name = @$platoon->WithArea->WithStorehouse->name . $platoon->WithArea->name . $platoon->name;
                }
                break;
            case $lenOrganizationLocationCode + 8:
                $shelf = Shelf::with(['WithPlatoon', 'WithPlatoon.WithArea', 'WithPlatoon.WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($shelf)) {
                    $storehouse_unique_code = $shelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $shelf->WithPlatoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $shelf->WithPlatoon->unique_code ?? '';
                    $shelf_unique_code = $shelf->unique_code;
                    $name = @$shelf->WithPlatoon->WithArea->WithStorehouse->name . @$shelf->WithPlatoon->WithArea->name . @$shelf->WithPlatoon->name . $shelf->name;
                }
                break;
            case $lenOrganizationLocationCode + 10:
                $tier = Tier::with(['WithShelf', 'WithShelf.WithPlatoon', 'WithShelf.WithPlatoon.WithArea', 'WithShelf.WithPlatoon.WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($tier)) {
                    $storehouse_unique_code = $tier->WithShelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $tier->WithShelf->WithPlatoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $tier->WithShelf->WithPlatoon->unique_code ?? '';
                    $shelf_unique_code = $tier->WithShelf->unique_code ?? '';
                    $tier_unique_code = $tier->unique_code;
                    $name = @$tier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @$tier->WithShelf->WithPlatoon->WithArea->name . @$tier->WithShelf->WithPlatoon->name . @$tier->WithShelf->name . $tier->name;
                }
                break;
            case $lenOrganizationLocationCode + 12:
                $position = Position::with(['WithTier', 'WithTier.WithShelf', 'WithTier.WithShelf.WithPlatoon', 'WithTier.WithShelf.WithPlatoon.WithArea', 'WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse'])->where('unique_code', $locationUniqueCode)->first();
                if (!empty($position)) {
                    $storehouse_unique_code = $position->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? '';
                    $area_unique_code = $position->WithTier->WithShelf->WithPlatoon->WithArea->unique_code ?? '';
                    $platoon_unique_code = $position->WithTier->WithShelf->WithPlatoon->unique_code ?? '';
                    $shelf_unique_code = $position->WithTier->WithShelf->unique_code ?? '';
                    $tier_unique_code = $position->WithTier->unique_code ?? '';
                    $position_unique_code = $position->unique_code;
                    $name = @$position->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @$position->WithTier->WithShelf->WithPlatoon->WithArea->name . @$position->WithTier->WithShelf->WithPlatoon->name . @$position->WithTier->WithShelf->name . @$position->WithTier->name . $position->name;
                }
                break;
            default:
                return [
                    'status' => 404,
                    'message' => '???????????????????????????',
                ];
        }

        return [
            'status' => 200,
            'message' => '??????',
            'data' => [
                'storehouse_unique_code' => $storehouse_unique_code,
                'area_unique_code' => $area_unique_code,
                'platoon_unique_code' => $platoon_unique_code,
                'shelf_unique_code' => $shelf_unique_code,
                'tier_unique_code' => $tier_unique_code,
                'position_unique_code' => $position_unique_code,
                'name' => $name,
            ]
        ];
    }

    /**
     * ????????????
     * @return array|JsonResponse
     */
    final public function takeStockScanCode()
    {
        try {
            $identityCode = request('identity_code', '');
            $locationUniqueCode = request('location_unique_code', '');
            if (empty($identityCode) || empty($locationUniqueCode)) return JsonResponseFacade::errorEmpty('????????????');
            $entireInstance = EntireInstance::with([])->where('identity_code', $identityCode)->first();
            $partInstance = PartInstance::with(['PartCategory'])->where('identity_code', $identityCode)->first();
            if (empty($entireInstance) && empty($partInstance)) return JsonResponseFacade::errorEmpty('?????????????????????');
            $data = [];
            if (!empty($entireInstance)) {
                if ($entireInstance->is_bind_location == 1) {
                    $message = '??????';
                } else {
                    $message = '??????';
                }
                $data = [
                    'message' => $message,
                    'identity_code' => $entireInstance->identity_code,
                    'category_name' => $entireInstance->category_name ?? '',
                    'sub_model_name' => $entireInstance->model_name ?? '',
                    'status' => $entireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '??????',
                ];
            }
            if (!empty($partInstance)) {
                if ($partInstance->is_bind_location == 1) {
                    $message = '??????';
                } else {
                    $message = '??????';
                }
                $data = [
                    'message' => $message,
                    'identity_code' => $partInstance->identity_code,
                    'category_name' => $partInstance->PartCategory->name ?? '',
                    'sub_model_name' => $partInstance->part_model_name ?? '',
                    'status' => $partInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '??????',
                ];
            }

            return JsonResponseFacade::data($data);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @return JsonResponse
     */
    final public function takeStock(Request $request)
    {
        try {
            $locationUniqueCode = $request->get('location_unique_code', '');
            $date = $request->get('date');
            if (empty($locationUniqueCode)) return JsonResponseFacade::errorEmpty('??????????????????????????????');
            $locationReturn = $this->_getLocationUniqueCode($locationUniqueCode);
            if ($locationReturn['status'] != 200) return JsonResponseFacade::errorEmpty($locationReturn['message']);

            $storehouse_unique_code = $locationReturn['data']['storehouse_unique_code'];
            $area_unique_code = $locationReturn['data']['area_unique_code'];
            $platoon_unique_code = $locationReturn['data']['platoon_unique_code'];
            $shelf_unique_code = $locationReturn['data']['shelf_unique_code'];
            $tier_unique_code = $locationReturn['data']['tier_unique_code'];
            $position_unique_code = $locationReturn['data']['position_unique_code'];
            $locationName = $locationReturn['data']['name'];

            $stockEntireInstances = EntireInstance::with(['WithPosition'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            $stockPartInstances = PartInstance::with(['WithPosition', 'PartCategory'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            if ($stockEntireInstances->isEmpty() && $stockPartInstances->isEmpty()) return JsonResponseFacade::errorEmpty('???????????????????????????');
            $realIdentityCodes = $request->get('identity_code', []);
            $realStockEntireInstances = EntireInstance::with(['WithPosition'])->whereIn('identity_code', $realIdentityCodes)->get();
            $realStockPartInstances = PartInstance::with(['WithPosition', 'PartCategory'])->whereIn('identity_code', $realIdentityCodes)->get();
            // ????????????
            $stockInstances = [];
            $realStockInstances = [];
            foreach ($stockEntireInstances as $stockEntireInstance) {
                $stockInstances[$stockEntireInstance->identity_code] = [
                    'identity_code' => $stockEntireInstance->identity_code,
                    'category_unique_code' => $stockEntireInstance->category_unique_code ?? '',
                    'category_name' => $stockEntireInstance->category_name ?? '',
                    'sub_model_unique_code' => $stockEntireInstance->model_unique_code ?? '',
                    'sub_model_name' => $stockEntireInstance->model_name ?? '',
                    'location_unique_code' => $stockEntireInstance->location_unique_code ?? '',
                    'location_name' => empty($stockEntireInstance->WithPosition) ? '' : $stockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $stockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $stockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $stockEntireInstance->WithPosition->WithTier->WithShelf->name . $stockEntireInstance->WithPosition->WithTier->name . $stockEntireInstance->WithPosition->name,
                    'status_name' => $stockEntireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '??????',
                ];
            }
            foreach ($stockPartInstances as $stockPartInstance) {
                $stockInstances[$stockPartInstance->identity_code] = [
                    'identity_code' => $stockPartInstance->identity_code,
                    'category_unique_code' => $stockPartInstance->part_category_id ?? '',
                    'category_name' => $stockPartInstance->PartCategory->name ?? '',
                    'sub_model_unique_code' => $stockPartInstance->part_model_unique_code ?? '',
                    'sub_model_name' => $stockPartInstance->part_model_name ?? '',
                    'location_unique_code' => $stockPartInstance->location_unique_code ?? '',
                    'location_name' => empty($stockPartInstance->WithPosition) ? '' : $stockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $stockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $stockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $stockPartInstance->WithPosition->WithTier->WithShelf->name . $stockPartInstance->WithPosition->WithTier->name . $stockPartInstance->WithPosition->name,
                    'status_name' => $stockPartInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '??????',
                ];
            }
            foreach ($realStockEntireInstances as $realStockEntireInstance) {
                $realStockInstances[$realStockEntireInstance->identity_code] = [
                    'identity_code' => $realStockEntireInstance->identity_code,
                    'category_unique_code' => $realStockEntireInstance->category_unique_code ?? '',
                    'category_name' => $realStockEntireInstance->category_name ?? '',
                    'sub_model_unique_code' => $realStockEntireInstance->model_unique_code ?? '',
                    'sub_model_name' => $realStockEntireInstance->model_name ?? '',
                    'location_unique_code' => $realStockEntireInstance->location_unique_code ?? '',
                    'location_name' => empty($realStockEntireInstance->WithPosition) ? '' : $realStockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $realStockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $realStockEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $realStockEntireInstance->WithPosition->WithTier->WithShelf->name . $realStockEntireInstance->WithPosition->WithTier->name . $realStockEntireInstance->WithPosition->name,
                    'status_name' => $realStockEntireInstance->status ?? '',
                    'material_type' => 'ENTIRE',
                    'material_type_name' => '??????',

                ];
            }
            foreach ($realStockPartInstances as $realStockPartInstance) {
                $realStockInstances[$realStockPartInstance->identity_code] = [
                    'identity_code' => $realStockPartInstance->identity_code,
                    'category_unique_code' => $realStockPartInstance->part_category_id ?? '',
                    'category_name' => $realStockPartInstance->PartCategory->name ?? '',
                    'sub_model_unique_code' => $realStockPartInstance->part_model_unique_code ?? '',
                    'sub_model_name' => $realStockPartInstance->part_model_name ?? '',
                    'location_unique_code' => $realStockPartInstance->location_unique_code ?? '',
                    'location_name' => empty($realStockPartInstance->WithPosition) ? '' : $realStockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $realStockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $realStockPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $realStockPartInstance->WithPosition->WithTier->WithShelf->name . $realStockPartInstance->WithPosition->WithTier->name . $realStockPartInstance->WithPosition->name,
                    'status_name' => $realStockPartInstance->status ?? '',
                    'material_type' => 'PART',
                    'material_type_name' => '??????',
                ];
            }
            // ??????
            // ??????
            $intersects = array_intersect_key($stockInstances, $realStockInstances);
            // ??????
            $losss = array_diff_key($stockInstances, $realStockInstances);
            // ??????
            $surpluss = array_diff_key($realStockInstances, $stockInstances);
            $takeStock = new TakeStock();
            $takeStockUniqueCode = $takeStock->getUniqueCode();
            $takeStockInstances = [];
            $data = [];
            foreach ($intersects as $identityCode => $intersect) {
                $takeStockInstances[] = [
                    'take_stock_unique_code' => $takeStockUniqueCode,
                    'stock_identity_code' => $identityCode,
                    'real_stock_identity_code' => $identityCode,
                    'difference' => '=',
                    'category_unique_code' => $intersect['category_unique_code'],
                    'category_name' => $intersect['category_name'],
                    'sub_model_unique_code' => $intersect['sub_model_unique_code'],
                    'sub_model_name' => $intersect['sub_model_name'],
                    'location_unique_code' => $intersect['location_unique_code'],
                    'location_name' => $intersect['location_name'],
                    'material_type' => $intersect['material_type'],
                ];
            }
            foreach ($losss as $identityCode => $loss) {
                $takeStockInstances[] = [
                    'take_stock_unique_code' => $takeStockUniqueCode,
                    'stock_identity_code' => $identityCode,
                    'real_stock_identity_code' => '',
                    'difference' => '-',
                    'category_unique_code' => $loss['category_unique_code'],
                    'category_name' => $loss['category_name'],
                    'sub_model_unique_code' => $loss['sub_model_unique_code'],
                    'sub_model_name' => $loss['sub_model_name'],
                    'location_unique_code' => $loss['location_unique_code'],
                    'location_name' => $loss['location_name'],
                    'material_type' => $loss['material_type'],
                ];
                $data[] = [
                    'message' => '??????',
                    'identity_code' => $identityCode,
                    'category_name' => $loss['category_name'],
                    'sub_model_name' => $loss['sub_model_name'],
                    'status_name' => $loss['status_name'],
                    'material_type' => $loss['material_type'],
                    'material_type_name' => $loss['material_type_name'],
                ];
            }

            foreach ($surpluss as $identityCode => $surplus) {
                $takeStockInstances[] = [
                    'take_stock_unique_code' => $takeStockUniqueCode,
                    'stock_identity_code' => '',
                    'real_stock_identity_code' => $identityCode,
                    'difference' => '+',
                    'category_unique_code' => $surplus['category_unique_code'],
                    'category_name' => $surplus['category_name'],
                    'sub_model_unique_code' => $surplus['sub_model_unique_code'],
                    'sub_model_name' => $surplus['sub_model_name'],
                    'location_unique_code' => $surplus['location_unique_code'],
                    'location_name' => $surplus['location_name'],
                    'material_type' => $surplus['material_type'],
                ];
                $data[] = [
                    'message' => '??????',
                    'identity_code' => $identityCode,
                    'category_name' => $surplus['category_name'],
                    'sub_model_name' => $surplus['sub_model_name'],
                    'status_name' => $surplus['status_name'],
                    'material_type' => $surplus['material_type'],
                    'material_type_name' => $surplus['material_type_name'],
                ];
            }
            $accountId = session('account.id');
            if (empty($losss) && empty($surpluss)) {
                // ?????????
                $take_stock = [
                    'unique_code' => $takeStockUniqueCode,
                    'state' => 'END',
                    'result' => 'NODIF',
                    'stock_diff' => count($losss),
                    'real_stock_diff' => count($surpluss),
                    'account_id' => $accountId,
                    'location_unique_code' => $locationUniqueCode,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'name' => $locationName ?? '??????',
                ];
            } else {
                // ?????????
                $take_stock = [
                    'unique_code' => $takeStockUniqueCode,
                    'state' => 'END',
                    'result' => 'YESDIF',
                    'stock_diff' => count($losss),
                    'real_stock_diff' => count($surpluss),
                    'account_id' => $accountId,
                    'location_unique_code' => $locationUniqueCode,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'name' => $locationName ?? '??????',
                ];
            }
            DB::transaction(function () use ($take_stock, $takeStockInstances, $accountId, $date) {
                DB::table('take_stocks')->where('account_id', $accountId)->where('state', 'START')->update(['state' => 'CANCEL', 'updated_at' => $date]);
                DB::table('take_stock_instances')->insert($takeStockInstances);
                DB::table('take_stocks')->insert($take_stock);
            });
            return JsonResponseFacade::data($data, '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????->??????or??????
     * @param string|null $identityCode
     * @return mixed
     */
    final public function postBindOfSearch(Request $request): JsonResponse
    {
        try {
            switch ($request->get('type')) {
                case 'entire':
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    if (!$entireInstanceIdentityCode) return JsonResponseFacade::errorEmpty('????????????????????????');
                    $entireInstance = EntireInstance::with([
                        'Station',
                        'Station.Parent',
                        'Category',
                        'EntireModel',
                        'SubModel',
                        'PartModel',
                        'PartInstances',
                        'PartInstances.PartCategory',
                    ])
                        ->where('identity_code', $entireInstanceIdentityCode)
                        ->firstOrFail();
                    return JsonResponseFacade::data($entireInstance);
                case 'part':
                    $partIdentityCode = $request->get('part_identity_code');
                    $partInstance = PartInstance::with([
                        'Category',
                        'EntireModel',
                        'PartCategory',
                    ])
                        ->where('identity_code', $partIdentityCode)
                        ->firstOrFail();
                    return JsonResponseFacade::data($partInstance);
                default:
                    return JsonResponseFacade::errorEmpty();
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????/??????/??????
     * @param string|null $identityCode
     * @return mixed
     */
    final public function postBind(Request $request): JsonResponse
    {
        try {
            switch ($request->get('type')) {
                case 'bind':
                    // ????????????
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    $newPartIdentityCode = $request->get('new_part_identity_code');
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    DB::transaction(function () use ($entireInstanceIdentityCode, $newPartIdentityCode, $date, $nickname) {
                        DB::table('part_instances')
                            ->where('identity_code', $newPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => $entireInstanceIdentityCode
                            ]);
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '????????????',
                                'description' => implode('???', [
                                    "??????:{$entireInstanceIdentityCode}????????????:{$newPartIdentityCode}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => '',
                            ]);
                    });
                    return JsonResponseFacade::created([], '????????????');
                case 'unbind':
                    // ????????????
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    $oldPartIdentityCode = $request->get('old_part_identity_code');
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    DB::transaction(function () use ($entireInstanceIdentityCode, $oldPartIdentityCode, $date, $nickname) {
                        DB::table('part_instances')
                            ->where('identity_code', $oldPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => null
                            ]);
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '????????????',
                                'description' => implode('???', [
                                    "??????:{$entireInstanceIdentityCode}????????????:{$oldPartIdentityCode}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => '',
                            ]);
                    });
                    return JsonResponseFacade::created([], '????????????');
                case 'change_bind':
                    // ????????????
                    $entireInstanceIdentityCode = $request->get('entire_instance_identity_code');
                    $newPartIdentityCode = $request->get('new_part_identity_code');
                    $oldPartIdentityCode = $request->get('old_part_identity_code');
                    $date = $request->get('date');
                    $nickname = session('account.nickname');
                    DB::transaction(function () use ($entireInstanceIdentityCode, $newPartIdentityCode, $oldPartIdentityCode, $date, $nickname) {
                        DB::table('part_instances')
                            ->where('identity_code', $oldPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => null
                            ]);
                        DB::table('part_instances')
                            ->where('identity_code', $newPartIdentityCode)
                            ->update([
                                'updated_at' => $date,
                                'entire_instance_identity_code' => $entireInstanceIdentityCode
                            ]);
                        // ????????????
                        EntireInstanceLog::with([])
                            ->create([
                                'created_at' => $date,
                                'updated_at' => $date,
                                'name' => '????????????',
                                'description' => implode('???', [
                                    "??????:{$newPartIdentityCode}????????????:{$oldPartIdentityCode}",
                                    "????????????{$nickname}"
                                ]),
                                'entire_instance_identity_code' => $entireInstanceIdentityCode,
                                'type' => 4,
                                'url' => '',
                                'operator_id' => session('account.id'),
                                'station_unique_code' => '',
                            ]);
                    });
                    return JsonResponseFacade::created([], '????????????');
                default:
                    return JsonResponseFacade::errorEmpty();
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return mixed
     */
    final public function postSparesStatistics(Request $request)
    {
        try {
            $identity_code = $request->get('identity_code');
            $entireInstance = EntireInstance::where('identity_code', $identity_code)->firstOrFail();
            // ????????????
            if ($entireInstance->status !== '??????') return JsonResponseFacade::errorEmpty('???????????????');
            if ($entireInstance->maintain_station_name) {
                // ??????
                $maintain_station_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_station_name', $entireInstance->maintain_station_name)
                    ->where('status', 'INSTALLING')
                    ->count();
                $maintain_workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $entireInstance->maintain_workshop_name)
                    ->where('status', 'INSTALLING')
                    ->count();
                // ????????????
                $scene_workshop_unique_code = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('parent_unique_code');
                // ??????id
                $stationId = DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('id');
                // ????????????
                $workshopName = DB::table('maintains')->where('unique_code', $scene_workshop_unique_code)->value('name');
                // ????????????
                // $entire_model_unique_code = DB::table('entire_models')->where('unique_code', $entireInstance->model_unique_code)->value('parent_unique_code');
                // ??????????????????
                $workshop_distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $workshopName)->value('distance') / 1000, 2);
                // ??????????????????
                $station_distance = 0;
                // ???????????????????????????????????????
                $current_workshop = DB::table('maintains')->where('type', 'WORKSHOP')->first();  // ??????????????????
                $distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $current_workshop->name)->value('distance') / 1000, 2);
                // ???????????????????????????
                $stations = DB::table('distance')->where('maintains_id', $stationId)->where('distance', '!=', 0)->orderBy('distance')->limit(2)->get()->toArray();
                // ????????????????????????????????????
                foreach ($stations as $key => $station) {
                    $stations[$key]->maintain_station_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_station_name', $station->maintains_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    $stations[$key]->distance = round($station->distance / 1000, 2);
                }
                #?????????????????????????????????
                $workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $current_workshop->name)
                    ->where('status', 'FIXED')
                    ->count();

                # ????????????
                $current_station = DB::table('maintains as s')
                    ->select([
                        's.name as station_name',
                        's.unique_code as station_unique_code',
                        'sw.name as workshop_name',
                        'sw.name as workshop_unique_code',
                        's.lon as station_lon',
                        's.lat as station_lat',
                        'sw.lon as workshop_lon',
                        'sw.lat as workshop_lat',
                    ])
                    ->join(DB::raw('maintains as sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                    ->where('s.deleted_at', null)
                    ->where('s.name', $entireInstance->maintain_station_name)
                    ->first();
                # ????????????
                $data = [
                    'belongToStation' => [
                        'name' => $entireInstance->maintain_station_name,
                        'count' => $maintain_station_num,
                        'distance' => $station_distance,
                        'lon' => $current_station->station_lon,
                        'lat' => $current_station->station_lat,
                    ],
                    'belongToWorkshop' => [
                        'name' => $workshopName,
                        'count' => $maintain_workshop_num,
                        'distance' => $workshop_distance,
                        'lon' => $current_station->workshop_lon,
                        'lat' => $current_station->workshop_lat,
                    ],
                    'nearStation' => $stations,
                    'WORKSHOP' => [
                        'name' => $current_workshop->name,
                        'count' => $workshop_num,
                        'distance' => $distance,
                        'lon' => $current_workshop->lon,
                        'lat' => $current_workshop->lat
                    ],
                ];
                return JsonResponseFacade::data($data);
            } else {
                // ??????
                // $maintain_station_num = 0;
                $maintain_workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $entireInstance->maintain_workshop_name)
                    ->where('status', 'INSTALLING')
                    ->count();
                // ????????????
                // $scene_workshop_unique_code = DB::table('maintains')->where('name', $entireInstance->maintain_workshop_name)->value('unique_code');
                $stationId = DB::table('maintains')->where('name', $entireInstance->maintain_workshop_name)->value('id');
                // ????????????
                // $entire_model_unique_code = DB::table('entire_models')->where('unique_code', $entireInstance->model_unique_code)->value('parent_unique_code');
                // ??????????????????
                $workshop_distance = 0;
                // ??????????????????
                // $station_distance = 0;
                // ???????????????????????????????????????
                $current_workshop = DB::table('maintains')->where('type', 'WORKSHOP')->first();
                $distance = round(DB::table('distance')->where('maintains_id', $stationId)->where('maintains_name', $current_workshop->name)->value('distance') / 1000, 2);
                // ???????????????????????????
                $stations = DB::table('distance')->where('maintains_id', $stationId)->where('distance', '!=', 0)->orderBy('distance')->limit(2)->get()->toArray();
                // ????????????????????????????????????
                foreach ($stations as $key => $station) {
                    $stations[$key]->maintain_station_num = DB::table('entire_instances')
                        ->where('category_unique_code', $entireInstance->category_unique_code)
                        ->where('model_unique_code', $entireInstance->model_unique_code)
                        ->where('maintain_station_name', $station->maintains_name)
                        ->where('status', 'INSTALLING')
                        ->count();
                    $stations[$key]->distance = round($station->distance / 1000, 2);
                }
                #?????????????????????????????????
                $workshop_num = DB::table('entire_instances')
                    ->where('category_unique_code', $entireInstance->category_unique_code)
                    ->where('model_unique_code', $entireInstance->model_unique_code)
                    ->where('maintain_workshop_name', $current_workshop->name)
                    ->where('status', 'FIXED')
                    ->count();

                # ????????????
                $scene_workshop = DB::table('maintains as sw')->where('sw.deleted_at', null)->where('name', $entireInstance->maintain_workshop_name)->where('type', 'SCENE_WORKSHOP')->first();
                $data = [
                    'belongToStation' => ['name' => '', 'count' => '', 'distance' => '', 'lon' => null, 'lat' => null],
                    'belongToWorkshop' => [
                        'name' => $entireInstance->maintain_workshop_name,
                        'count' => $maintain_workshop_num,
                        'distance' => $workshop_distance,
                        'lon' => $scene_workshop->lon,
                        'lat' => $scene_workshop->lat,
                    ],
                    'nearStation' => $stations,
                    'WORKSHOP' => ['name' => $current_workshop->name, 'count' => $workshop_num, 'distance' => $distance, 'lon' => $current_workshop->lon, 'lat' => $current_workshop->lat],
                ];
                return JsonResponseFacade::data($data);
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????(??????????????????/???????????????or??????????????????)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSearch(Request $request): JsonResponse
    {
        try {
            $search_type = $request->get('search_type');
            if ($search_type == 'entire_instances') {
                // ??????????????????/???????????????
                if ($request->get('code')) {
                    // ??????????????????
                    $code = $request->get('code');
                    if ((substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14)) {
                        // ??????????????????->????????????
                        $field = 'identity_code';
                    } else {
                        // ??????????????????->?????????
                        $field = 'serial_number';
                    }
                    $entireInstance = EntireInstance::with([
                        'Station',
                        'Station.Parent',
                        'Category',
                        // 'EntireModel',
                        'SubModel',
                        'SubModel.Parent',
                    ])
                        ->where($field, $code)
                        ->get()
                        ->toArray();
                    return JsonResponseFacade::data($entireInstance);
                } else {
                    // ???????????????

                }
            } else {
                // ??????????????????

            }

            // switch ($code) {
            // case (substr($code, 0, 1) == "Q" && strlen($code) == 19) || (substr($code, 0, 1) == "S" && strlen($code) == 14) :
            // $field = 'identity_code';
            // break;
            // default :
            // $field = 'serial_number';
            // break;
            // }
            // $entireInstance = EntireInstance::with([
            // 'Station',
            // 'Station.Parent',
            // 'Category',
            // 'EntireModel',
            // 'SubModel',
            // ])
            // ->where($field, $code)
            // ->get()
            // ->toArray();
            // return JsonResponseFacade::data($entireInstance);
        } catch (ModelNotFoundException $e) {
            return response()->json(['msg' => '???????????????'], 404);
        } catch (Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ????????????
     * @param string|null $identity_code
     * @return mixed
     */
    final public function getEntireInstance(string $identity_code = ''): JsonResponse
    {
        try {
            $entire_instance = EntireInstance::with([
                "BreakdownLogs",
                "Station",
                "Station.Parent",
                "Category",
                "EntireModel",
                "EntireModel.Parent",
                "SubModel",
                "PartModel",
                "PartInstances",
                "ParentInstance",
                "FixWorkflow",
                "FixWorkflows",
                "Line",
                "EntireInstanceLogs" => function ($EntireInstanceLogs) {
                    $EntireInstanceLogs
                        ->orderByDesc("created_at")
                        ->orderByDesc("id");
                },
                "WithSendRepairInstances",
                "WithSendRepairInstances.WithSendRepair",
                "WithPosition",
                "WithPosition.WithTier",
                "WithPosition.WithTier.WithShelf",
                "WithPosition.WithTier.WithShelf.WithPlatoon",
                "WithPosition.WithTier.WithShelf.WithPlatoon.WithArea",
                "WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse",
            ])
                ->where("identity_code", $identity_code)
                ->firstOrFail();
            $entire_instance->location_name = @$entire_instance->WithPosition ? @$entire_instance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @@$entire_instance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . @$entire_instance->WithPosition->WithTier->WithShelf->WithPlatoon->name . @$entire_instance->WithPosition->WithTier->WithShelf->name . @$entire_instance->WithPosition->WithTier->name . @$entire_instance->WithPosition->name : '';  // ????????????
            $entire_instance->is_overdue = boolval(strtotime($entire_instance->scarping_at) < time());  // ????????????
            $entire_instance->maintain_location_code = InstallPosition::getRealName($entire_instance->maintain_location_code ?? '') ?? $entire_instance->maintain_location_code;
            $entire_instance->install_position_name = $entire_instance->maintain_location_code . (($entire_instance->crossroad_number ? $entire_instance->crossroad_number . ' ' : '') . $entire_instance->open_direction);
            $entire_instance->life_year = @$entire_instance->SubModel ? ($entire_instance->SubModel->life_year ?? 15) : ($entire_instance->EntireModel->life_year ?? 15);
            $entire_instance->line_name2 = @$entire_instance->Line->name ?: '';
            $entire_instance->last_installed_at = $entire_instance->installed_at ?: '';
            $entire_instance->next_fixing_at = $entire_instance->next_fixing_time ? date('Y-m-d', $entire_instance->next_fixing_time) : '';

            if ($entire_instance->entire_model_unique_code == $entire_instance->model_unique_code) {
                // ????????????????????????????????????????????????
                $sm = DB::table('entire_models as sm')
                    ->selectRaw(implode(',', ['sm.*', 'em.name as em_name']))
                    ->join(DB::raw('entire_models em'), 'sm.parent_unique_code', '=', 'em.unique_code')
                    ->where('sm.is_sub_model', true)
                    ->whereNull('sm.deleted_at')
                    ->where('sm.unique_code', $entire_instance->model_unique_code)
                    ->first();

                $entire_instance->entire_model_name = $sm ? $sm->em_name : '';
            } else {
                // ????????????????????????????????????
                $pm = DB::table('part_models as pm')
                    ->select([
                        'pm.name as sub_model_name',
                        'em.name as entire_model_name',
                    ])
                    ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                    ->whereNull('pm.deleted_at')
                    ->whereNull('em.deleted_at')
                    ->where('em.is_sub_model', false)
                    ->where('pm.unique_code', $entire_instance->model_unique_code)
                    ->first();
                $entire_instance->entire_model_name = $pm->entire_model_name;
            }

            // $entire_instance->maintain_station_name = $entire_instance->maintain_station_name ?: ($entire_instance->ParentInstance->maintain_station_name ?: "");
            // $entire_instance->maintain_workshop_name = $entire_instance->maintain_workshop_name ?: ($entire_instance->ParentInstance->maintain_workshop_name ?: "");
            // $entire_instance->maintain_location_code = $entire_instance->maintain_location_code ?: ($entire_instance->ParentInstance->maintain_location_code ?: "");
            // $entire_instance->maintain_section_name = $entire_instance->maintain_section_name ?: ($entire_instance->ParentInstance->maintain_section_name ?: "");
            // $entire_instance->maintain_send_or_receive = $entire_instance->maintain_send_or_receive ?: ($entire_instance->ParentInstance->maintain_send_or_receive ?: "");
            // $entire_instance->maintain_signal_post_main_or_indicator = $entire_instance->maintain_signal_post_main_or_indicator ?: ($entire_instance->ParentInstance->maintain_signal_post_main_or_indicator ?: "");
            // $entire_instance->maintain_signal_post_main_light_position = $entire_instance->maintain_signal_post_main_light_position ?: ($entire_instance->ParentInstance->maintain_signal_post_main_light_position ?: "");
            // $entire_instance->maintain_signal_post_indicator_light_position = $entire_instance->maintain_signal_post_indicator_light_position ?: ($entire_instance->ParentInstance->maintain_signal_post_indicator_light_position ?: "");

            // ?????????????????????
            if (@$entire_instance->EntireModel->fix_cycle_value == 0 && @$entire_instance->EntireModel->Parent->fix_cycle_value == 0) $entire_instance->fix_cycle_value = '???????????????';

            return JsonResponseFacade::data(['entire_instance' => $entire_instance]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('???????????????');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????????
     */
    final public function getFactories()
    {
        try {
            return JsonResponseFacade::dict(['factories' => DB::table('factories as f')->whereNull('f.deleted_at')->get(),]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????
     */
    final public function getStatuses()
    {
        try {
            return JsonResponseFacade::dict(['statuses' => EntireInstance::$STATUSES,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ????????????????????????
     * @return mixed
     */
    final public function getSourceTypes()
    {
        try {
            return JsonResponseFacade::dict(['source_types' => EntireInstance::$SOURCE_TYPES,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????
     * @return mixed
     */
    final public function getLines()
    {
        try {
            $lines = Line::with(['Stations'])->get();

            return JsonResponseFacade::dict(['lines' => $lines,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????
     * @return JsonResponse
     */
    final public function getEntireInstances(): JsonResponse
    {
        try {
            $entireInstances = ModelBuilderFacade::init(
                request(),
                EntireInstance::with([])
            )
                ->all();

            return JsonResponseFacade::data($entireInstances);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @param string $identity_code
     */
    final public function putEntireInstance(Request $request, string $identity_code)
    {
        /**
         * serial_number ?????????
         * factory_name ??????
         * factory_device_code ????????????
         * maintain_station_name ????????????
         * maintain_workshop_name ????????????
         * maintain_location_code ????????????
         * crossroad_number ?????????
         * open_direction ??????
         * to_direction ??????
         * traction ??????
         * line_name ??????
         * made_at ????????????
         * scarping_at ????????????????????????
         * said_rod ???????????????
         * crossroad_type ????????????
         * next_fixing_time ?????????????????????
         * next_fixing_month
         * next_fixing_day
         * extrusion_protect ??????????????????
         * note ??????
         * last_installed_time ??????????????????
         * last_out_at ??????????????????
         * source_type ????????????
         * source_name ????????????
         */
        try {
            $entire_instance = EntireInstance::with(['EntireModel', 'EntireModel.Parent',])->where('identity_code', $identity_code)->firstOrFail();

            $serial_number = $request->get('serial_number', '') ?? '';
            // ???????????????????????????
            // if ($serial_number)
            //     if (DB::table('entire_instances as ei')
            //         ->whereNull('ei.deleted_at')
            //         ->where('id', '<>', $entire_instance->id)
            //         ->where('ei.serial_number', $serial_number)
            //         ->where('ei.status', '<>', 'SCRAP')
            //         ->where('entire_model_unique_code', $entire_instance->entire_model_unique_code)
            //         ->exists())
            //         return JsonResponseFacade::errorForbidden('??????????????????????????????');

            $factory_name = $request->get('factory_name');
            if ($factory_name)
                if (!DB::table('factories as f')->whereNull('f.deleted_at')->exists())
                    return JsonResponseFacade::errorEmpty('???????????????');

            $factory_device_code = $request->get('factory_device_code');

            // $maintain_workshop_name = $request->get('maintain_workshop_name');
            // $scene_workshop = null;
            // if ($maintain_workshop_name) {
            //     $scene_workshop = DB::table('maintains as sc')->whereNull('sc.deleted_at')->where('sc.name', $maintain_workshop_name)->first();
            //     if (!$scene_workshop) return JsonResponseFacade::errorEmpty('?????????????????????');
            // }
            //
            // $maintain_station_name = $request->get('maintain_station_name');
            // $station = null;
            // if ($maintain_station_name) {
            //     $station = Maintain::with(['Parent'])->where('name', $maintain_station_name)->first();
            //     if (!$station) return JsonResponseFacade::errorValidate('???????????????');
            //     if (!$station->Parent) return JsonResponseFacade::errorForbidden('??????????????????????????????????????????????????????');
            //     $scene_workshop = $station->Parent;
            // }

            // $maintain_location_code = $request->get('maintain_location_code');
            // if ($maintain_location_code)
            //     if (DB::table('entire_instances as ei')
            //             ->whereNull('ei.deleted_at')
            //             ->where('ei.status', '<>', 'SCRAP')
            //             ->where('ei.id', '!=', $entire_instance->id)
            //             ->where('ei.maintain_location_code', $maintain_location_code)
            //             ->exists() && $maintain_location_code != '??????')
            //         return JsonResponseFacade::errorForbidden('???????????????????????????');

            $crossroad_number = $request->get('crossroad_number');
            $open_direction = $request->get('open_direction');
            $line_name = $request->get('line_name');

            $made_at = $request->get('made_at');
            $scarping_at = null;
            if ($made_at) {
                try {
                    $made_at = Carbon::parse($made_at);
                    $life_year = @$entire_instance->EntireModel->life_year ?: (@$entire_instance->EntireModel->Parent->life_year ?: 0);
                    if ($life_year) {
                        $scarping_at = $made_at->copy()->addYears($life_year);
                    }
                } catch (Exception $e) {
                    return JsonResponseFacade::errorForbidden('????????????????????????');
                }
            }

            $said_rod = $request->get('said_rod');
            $crossroad_type = $request->get('crossroad_type');
            $extrusion_protect = $request->get('extrusion_protect');
            $note = $request->get('note');
            $last_out_at = $request->get('last_out_at');
            $next_fixing_time = null;
            if ($last_out_at) {
                try {
                    $last_out_at = Carbon::parse($last_out_at);
                    $fix_cycle_value = @$entire_instance->EntireModel->fix_cycle_value ?: (@$entire_instance->EntireModel->Parent->fix_cycle_value ?: 0);
                    if ($fix_cycle_value) {
                        $next_fixing_time = $last_out_at->copy()->addYears($fix_cycle_value);
                    }
                } catch (Exception $e) {
                    return JsonResponseFacade::errorForbidden('????????????????????????');
                }
            }

            $last_installed_time = $request->get('last_installed_at');
            if ($last_installed_time) {
                try {
                    $last_installed_time = Carbon::parse($last_installed_time);
                } catch (Exception $e) {
                    return JsonResponseFacade::errorForbidden('????????????????????????');
                }
            }

            $source_type = $request->get('source_type');
            $source_name = $request->get('source_name');

            $update_datum = [
                'serial_number' => @$serial_number ?: '',
                'factory_name' => @$factory_name ?: '',
                'factory_device_code' => @$factory_device_code ?: '',
                // 'maintain_station_name' => @$station->name ?: '',
                // 'maintain_workshop_name' => @$scene_workshop->name ?: '',
                // 'maintain_location_code' => @$maintain_location_code ?: '',
                'crossroad_number' => @$crossroad_number ?: '',
                'open_direction' => @$open_direction ?: '',
                'line_name' => @$line_name ?: '',
                'made_at' => @$made_at ? $made_at->format('Y-m-d') : null,
                'scarping_at' => @$scarping_at ? $scarping_at->format('Y-m-d') : null,
                'said_rod' => @$said_rod ?: '',
                'crossroad_type' => @$crossroad_type ?: '',
                'next_fixing_time' => @$next_fixing_time ? $next_fixing_time->timestamp : null,
                'next_fixing_month' => @$next_fixing_time ? $next_fixing_time->format('Y-m-01') : null,
                'next_fixing_day' => @$next_fixing_time ? $next_fixing_time->format('Y-m-d') : null,
                'extrusion_protect' => @$extrusion_protect ?: '',
                'note' => @$note ?: '',
                'installed_at' => @$last_installed_time ?? null,
                'last_installed_at' => @$last_installed_time ? $last_installed_time->format('Y-m-d') : null,
                'last_out_at' => @$last_out_at ? $last_out_at->format('Y-m-d') : null,
                'source_type' => @$source_type ?: '',
                'source_name' => @$source_name ?: '',
            ];

            // $update_datum = array_filter($update_datum, function ($val) {
            //     return !empty($val);
            // });

            $entire_instance->fill($update_datum)->saveOrFail();

            return JsonResponseFacade::updated([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("?????????????????????");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * V250??????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postTaskList(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type');
            $v250TaskOrders = V250TaskOrder::with([
                'SceneWorkshop',
                'MaintainStation',
                'WorkAreaByUniqueCode',
                'Principal'
            ])
                ->where('type', $type)
                ->where('status', 'PROCESSING')
                ->where('work_area_unique_code', session('account.work_area_unique_code'))
                ->orderByDesc('id')
                ->paginate();
            return JsonResponseFacade::data($v250TaskOrders);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250??????????????????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postListDetails(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $v250TaskOrders = V250TaskOrder::with([
                'SceneWorkshop',
                'MaintainStation',
                'WorkAreaByUniqueCode',
                'V250TaskEntireInstances',
            ])
                ->where('serial_number', $serialNumber)
                ->get();
            $v250_task_entire_instances = V250TaskEntireInstance::with([
                'EntireInstance',
                'EntireInstance.Category',
                'EntireInstance.EntireModel',
                'EntireInstance.SubModel'
            ])
                ->where('v250_task_order_sn', $serialNumber)
                ->paginate();
            $data = [
                'workshopName' => $v250TaskOrders[0]->SceneWorkshop->name,
                'stationName' => $v250TaskOrders[0]->MaintainStation->name,
                'workAreaName' => $v250TaskOrders[0]->WorkAreaByUniqueCode->name,
                'workAreaUniqueCode' => $v250TaskOrders[0]->WorkAreaByUniqueCode->unique_code,
                'expiringAt' => substr($v250TaskOrders[0]->expiring_at, 0, 10),
                'taskOrderCount' => $v250_task_entire_instances->count(),
                'workshopOutCount' => $v250_task_entire_instances->where('is_out', true)->count(),
                'entire_instances' => $v250_task_entire_instances
            ];
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250????????????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postPersonnel(Request $request): JsonResponse
    {
        try {
            $workAreaUniqueCode = $request->get('work_area_unique_code');
            $personnel = Account::where('work_area_unique_code', $workAreaUniqueCode)->get(['nickname', 'id']);
            return JsonResponseFacade::data($personnel);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250??????????????????(??????)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopStayOut(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $v250workshopStayOut = DB::table('v250_workshop_stay_out')->where('v250_task_orders_serial_number', $serialNumber)->where('status', 'PROCESSING')->get()->toArray();
            return JsonResponseFacade::data($v250workshopStayOut);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250??????????????????????????????(??????)
     * @param Request $request
     * @return JsonResponse
     */
    final public function postWorkshopStayOutEntireInstances(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $v250TaskEntireInstances = V250WorkshopOutEntireInstances::with([
                'EntireInstance',
                'EntireInstance.SubModel',
                'EntireInstance.PartModel',
                'EntireInstance.WithPosition',
                'WithPosition',
                'WithPosition.WithTier',
                'WithPosition.WithTier.WithShelf',
                'WithPosition.WithTier.WithShelf.WithPlatoon',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea',
                'WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
            ])->where('v250_workshop_stay_out_serial_number', $serialNumber)
                ->get();
            return JsonResponseFacade::data($v250TaskEntireInstances);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postOverhaul(Request $request): JsonResponse
    {
        try {
            $serialNumber = $request->get('serial_number');
            $accountId = $request->get('account_id');
            $identityCodes = $request->get('identity_codes');
            DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $serialNumber)->whereIn('entire_instance_identity_code', $identityCodes)->update(['fixer_id' => $accountId]);
            DB::table('entire_instances')->whereIn('identity_code', $identityCodes)->update([
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 'FIXING',
                'is_overhaul' => '1'
            ]);
            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250??????(??????)
     * @param Request $request
     * @return JsonResponse
     */
    final public function passWorkshopOut(Request $request): JsonResponse
    {
        try {
            $contactName = $request->get('contact_name');
            $contactPhone = $request->get('contact_phone');
            try {
                $date = Carbon::parse(($request->get('date', now()->format('Y-m-d H:i:s')) ?? now()->format('Y-m-d H:i:s')))->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                return JsonResponseFacade::errorForbidden('????????????????????????');
            }

            $sn = $request->get('serial_number');
            $accountNickname = session('account.nickname');
            $accountId = session('account.id');
            $workAreaId = session('account.work_area');
            $identityCodes = $request->get('identity_codes');
            $serialNumber = DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->where('entire_instance_identity_code', $identityCodes[0])->value('v250_task_orders_serial_number');
            $sign_img = $request->get('sign_img', '') ?? '';

            if (!$sign_img) return JsonResponseFacade::errorValidate('????????????');

            $entireInstances = EntireInstance::with([
                'Station',
                'Station.Parent',
            ])
                ->whereIn('identity_code', $identityCodes)
                ->get();

            DB::beginTransaction();
            // ???????????????
            $warehouseReportId = DB::table('warehouse_reports')->insertGetId([
                'created_at' => $date,
                'updated_at' => $date,
                'processor_id' => $accountId,
                'processed_at' => $date,
                'connection_name' => $contactName,
                'connection_phone' => $contactPhone,
                'type' => 'INSTALL',
                'direction' => 'OUT',
                'serial_number' => CodeFacade::makeSerialNumber('WAREHOUSE_OUT'),
                'work_area_id' => $workAreaId,
                'work_area_unique_code' => session('account.work_area_unique_code'),
                'sign_img' => $sign_img,
            ]);
            $warehouseReportSerialNumber = DB::table('warehouse_reports')->where('id', $warehouseReportId)->value('serial_number');

            $entireInstances->each(function ($entireInstance)
            use ($request, $date, $accountNickname, $contactName, $contactPhone, $warehouseReportSerialNumber) {
                // ??????????????????
                $entireInstance->fill([
                    'updated_at' => $date,
                    'last_out_at' => $date,
                    'status' => 'TRANSFER_OUT',
                    'next_fixing_time' => null,
                    'next_fixing_month' => null,
                    'next_fixing_day' => null,
                    'in_warehouse_breakdown_explain' => '',
                    'last_warehouse_report_serial_number_by_out' => $warehouseReportSerialNumber,
                    'location_unique_code' => '',
                    'is_bind_location' => 0,
                    'is_overhaul' => '0'
                ])
                    ->saveOrFail();

                // ?????????????????????
                EntireInstanceFacade::nextFixingTimeWithIdentityCode($entireInstance->identity_code, $date);

                // ????????????????????????
                DB::table('warehouse_report_entire_instances')->insert([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'warehouse_report_serial_number' => $warehouseReportSerialNumber,
                    'entire_instance_identity_code' => $entireInstance->identity_code,
                ]);

                // ??????????????????
                EntireInstanceLog::with([])
                    ->create([
                        'name' => '????????????',
                        'description' => implode('???', [
                            '????????????' . $accountNickname,
                            '????????????' . @$contactName ?: '',
                            '???????????????' . @$contactPhone ?: '',
                            '?????????' . @$entireInstance->Station->Parent ? @$entireInstance->Station->Parent->name : '' . ' ' . @$entireInstance->Station->Parent->name ?? '',
                            // '???????????????' . @$entireInstance->maintain_location_code ?? '' . @$entireInstance->crossroad_number ?? '',
                        ]),
                        'entire_instance_identity_code' => $entireInstance->identity_code,
                        'type' => 1,
                        'url' => "/warehouse/report/{$warehouseReportSerialNumber}",
                        'operator_id' => session('account.id'),
                        'station_unique_code' => @$entireInstance->Station->unique_code ?? '',
                    ]);
            });
            if (!DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->exists()) {
                DB::table('v250_workshop_stay_out')->where('serial_number', $sn)->update([
                    'updated_at' => $date,
                    'finished_at' => $date,
                    'status' => 'DONE',
                ]);
            }

            DB::table('v250_workshop_out_entire_instances')->where('v250_workshop_stay_out_serial_number', $sn)->whereIn('entire_instance_identity_code', $identityCodes)->delete();
            DB::table('v250_task_entire_instances')->where('v250_task_order_sn', $serialNumber)->whereIn('entire_instance_identity_code', $identityCodes)->update(['is_out' => 1]);
            DB::commit();
            return JsonResponseFacade::created([], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @param int $id
     * @return mixed
     */
    final public function getAccount(int $id)
    {
        try {
            $account = Account::with([])->where('id', $id)->first();

            return JsonResponseFacade::data(['account' => $account]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @return mixed
     */
    final public function getAccounts(): JsonResponse
    {
        try {
            $accounts = ModelBuilderFacade::init(request(), Account::with([]))
                ->extension(function ($Account) {
                    return $Account->select([
                        'id',
                        'account',
                        'nickname',
                    ]);
                })
                ->all();
            return JsonResponseFacade::data(['accounts' => $accounts]);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????
     * @params string type
     * @return JsonResponse
     */
    final public function getBreakdownOrders(): JsonResponse
    {
        try {
            $breakdownOrders = ModelBuilderFacade::init(request(), RepairBaseBreakdownOrder::with([
                'InEntireInstances',
                'InEntireInstances',
                'OutEntireInstances',
                'Processor',
            ]))->all();

            return JsonResponseFacade::data($breakdownOrders);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postSceneBackIn(Request $request): JsonResponse
    {
        try {
            // ??????????????????????????????
            $v250_task_entire_instances = V250TaskEntireInstance::with(['V250TaskOrder'])
                ->whereHas('V250TaskOrder', function ($V250TaskOrder) {
                    $V250TaskOrder->where('type', 'NEW_STATION');
                })
                ->where('is_out', true)
                ->get();
            $diff = array_diff($request->get('identityCodes'), $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray());
            if ($diff) return JsonResponseFacade::errorForbidden('????????????????????????????????????????????????', ['error_identity_codes' => $diff]);
            $intersect = array_values(array_intersect($request->get('identityCodes'), $v250_task_entire_instances->pluck('entire_instance_identity_code')->toArray()));

            $edited_entire_instances = [];
            DB::beginTransaction();
            // ??????????????????
            V250TaskEntireInstance::with([])->whereIn('entire_instance_identity_code', $intersect)->update(['is_out' => false]);
            $warehouse_report = WarehouseReport::with([])->create([
                'processor_id' => session('account.id'),
                'processed_at' => date('Y-m-d H:i:s'),
                'connection_name' => '',
                'connection_phone' => '',
                'type' => 'SCENE_BACK',
                'direction' => 'IN',
                'serial_number' => $warehouse_report_sn = CodeFacade::makeSerialNumber('SCENE_BACK_IN'),
                'status' => 'DONE',
            ]);
            foreach ($intersect as $identity_code) {
                $entire_instance = EntireInstance::with([])->where('identity_code', $identity_code)->first();
                WarehouseReportEntireInstance::with([])->create([
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'warehouse_report_serial_number' => $warehouse_report_sn,
                    'entire_instance_identity_code' => $identity_code,
                    'maintain_station_name' => $entire_instance->maintain_station_name,
                    'maintain_location_code' => $entire_instance->maintain_location_code,
                    'crossroad_number' => $entire_instance->crossroad_number,
                    'traction' => $entire_instance->traction,
                    'line_name' => $entire_instance->line_name,
                    'crossroad_type' => $entire_instance->crossroad_type,
                    'extrusion_protect' => $entire_instance->extrusion_protect,
                    'point_switch_group_type' => $entire_instance->point_switch_group_type,
                    'open_direction' => $entire_instance->open_direction,
                    'said_rod' => $entire_instance->said_rod,
                    'is_out' => false,
                ]);
                EntireInstanceLog::with([])->create([
                    'name' => '????????????',
                    'description' => "????????????" . session('account.nickname'),
                    'entire_instance_identity_code' => $identity_code,
                    'type' => 1,
                    'url' => "/warehouse/report/{$warehouse_report->serial_number}",
                    'material_type' => 'ENTIRE',
                ]);

                // ??????????????????
                $entire_instance->fill([
                    'maintain_workshop_name' => env('JWT_ISS'),
                    'status' => 'FIXED',
                    'maintain_location_code' => null,
                    'crossroad_number' => null,
                    'next_fixing_time' => null,
                    'next_fixing_month' => null,
                    'next_fixing_day' => null,
                ])
                    ->saveOrFail();
                $edited_entire_instances[] = $entire_instance;
            }
            DB::commit();

            return JsonResponseFacade::created(['entire_instances' => $entire_instance], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250??????->????????????
     * @return mixed
     */
    final public function postOverhaulOfSearch(Request $request): JsonResponse
    {
        try {
            $identityCode = $request->get('identity_code');
            $entireInstance = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel'
            ])
                ->where('identity_code', $identityCode)
                ->where('is_overhaul', '1')
                ->get()
                ->toArray();
            if (!$entireInstance) {
                return JsonResponseFacade::errorEmpty();
            }
            return JsonResponseFacade::data($entireInstance);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * V250????????????
     * @return mixed
     */
    final public function postCompleteOverhaul(Request $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $selected_for_fix_misson = $request->get('identity_codes');
                $deadLine = $request->get('date');
                DB::table('v250_task_entire_instances as tei')
                    ->join('v250_task_orders as to', 'to.serial_number', 'tei.v250_task_order_sn')
                    ->where('to.status', 'PROCESSING')
                    ->whereIn('entire_instance_identity_code', $selected_for_fix_misson)
                    ->update(['tei.fixed_at' => $deadLine]);
                foreach ($selected_for_fix_misson as $entire_instance) {
                    $deadAt = DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->value('deadline');
                    if (strtotime($deadAt) >= strtotime($deadLine)) {
                        DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->update(['fixed_at' => $deadLine, 'status' => '1']);
                    } else {
                        DB::table('overhaul_entire_instances')->where('status', '0')->where('entire_instance_identity_code', $entire_instance)->update(['fixed_at' => $deadLine, 'status' => '2']);
                    }
                    //                    if (DB::table('entire_instances')->where('v250_task_order_sn', null)->where('identity_code', $entire_instance)->exists()) {
                    //                        DB::table('entire_instances')->where('identity_code', $entire_instance)->update([
                    //                            'updated_at' => date('Y-m-d H:i:s'),
                    //                            'is_overhaul' => '0'
                    //                        ]);
                    //                    }
                    DB::table('entire_instances')->where('identity_code', $entire_instance)->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status' => 'FIXED',
                        'is_overhaul' => '0'
                    ]);
                }
            });
            return JsonResponseFacade::created([], '????????????');
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postCheckDevice(Request $request): JsonResponse
    {
        try {
            $entire_instance_identity_codes_in_v250_task = V250TaskEntireInstance::with([
                'V250TaskOrder',
                'EntireInstance',
                'Fixer',
                'Checker'
            ])
                ->whereHas('V250TaskOrder', function ($V250TaskOrder) {
                    $V250TaskOrder->where('status', 'UNDONE');
                })
                ->where('checker_id', 0)
                ->pluck('entire_instance_identity_code')
                ->toArray();
            if (empty($entire_instance_identity_codes_in_v250_task)) return JsonResponseFacade::errorEmpty('?????????????????????????????????????????????');
            $entire_instance_identity_codes = EntireInstance::with([])->where('v250_task_order_sn', '')->whereIn('identity_code', $request->get('identityCodes'))->pluck('identity_code')->toArray();
            $diff = array_diff($request->get('identityCodes'), array_unique(array_merge($entire_instance_identity_codes_in_v250_task, $entire_instance_identity_codes)));
            if ($diff) return JsonResponseFacade::errorForbidden('????????????????????????', ['error_identity_codes' => $diff]);
            $now = date('Y-m-d H:i:s');

            $edited_entire_instances = [];
            DB::beginTransaction();
            $entire_instances_in_v250_task = V250TaskEntireInstance::with(['V250TaskOrder', 'EntireInstance', 'Fixer', 'Checker'])
                ->whereHas('V250TaskOrder', function ($V250TaskOrder) {
                    $V250TaskOrder->where('status', 'UNDONE');
                })
                ->where('checker_id', 0)
                ->whereIn('entire_instance_identity_code', $request->get('identityCodes'))
                ->chunkByid(50, function ($v250_task_entire_instances) use ($now, &$edited_entire_instances) {
                    foreach ($v250_task_entire_instances as $v250_task_entire_instance) {
                        if (!$v250_task_entire_instance->Fixer) return JsonResponseFacade::errorForbidden("???????????????{$v250_task_entire_instance->entire_instance_identity_code} ??????????????????/?????????");
                        // throw new CheckDeviceException("???????????????{$v250_task_entire_instance->entire_instance_identity_code} ??????????????????/?????????");
                        FixWorkflowFacade::mockEmpty(
                            $v250_task_entire_instance->EntireInstance,
                            $v250_task_entire_instance->fixed_at ?? $now,
                            $now,
                            $v250_task_entire_instance->fixer_id,
                            session('account.id')
                        );

                        $v250_task_entire_instance->EntireInstance->fill(['status' => 'FIXED'])->saveOrFail();  // ??????????????????
                        $v250_task_entire_instance->fill(['checker_id' => session('account.id'), 'checked_at' => $now])->saveOrFail();  // ??????????????????????????????
                        $edited_entire_instances[] = $v250_task_entire_instance->EntireInstance;
                    }
                });

            EntireInstance::with([])
                ->whereIn('identity_code', $request->get('identityCodes'))
                ->chunkById(50, function ($entire_instances) use ($now, &$edited_entire_instances) {
                    foreach ($entire_instances as $entire_instance) {
                        FixWorkflowFacade::mockEmpty(
                            $entire_instance,
                            $now,
                            $now,
                            session('account.id'),
                            session('account.id')
                        );

                        $entire_instance->fill(['status' => 'FIXED'])->saveOrFail(); // ??????????????????
                        $edited_entire_instances[] = $entire_instance;
                    }
                });
            DB::commit();

            return JsonResponseFacade::created(['entire_instances' => $edited_entire_instances], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????????????????
     */
    final public function getStationInstallLocationRecords(): JsonResponse
    {
        try {
            $station_install_location_records = StationInstallLocationRecord::with(['Station'])
                ->orderByDesc('id')
                ->where('processor_id', session('account.id'))
                ->get();

            return JsonResponseFacade::data(['station_install_location_records' => $station_install_location_records]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????
     * @param string $paragraph_unique_code
     * @return JsonResponse
     */
    final public function getSceneWorkshops(): JsonResponse
    {
        try {
            $scene_workshops = Maintain::with(['Subs'])
                ->where('parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('type', 'SCENE_WORKSHOP')
                ->get();

            return JsonResponseFacade::data(['scene_workshops' => $scene_workshops]);
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @return JsonResponse
     */
    final public function postCorrectMaintainLocation(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            // $maintain = Maintain::with(['Parent'])
            //     ->where('type', 'STATION')
            //     ->where('name', $request->get('maintain_station_name'))
            //     ->first();
            // if (!$maintain) return response()->json(['msg' => '??????????????????', 'status' => 404], 404);

            $entire_instance = EntireInstance::with([])
                ->where('identity_code', $request->get('entire_instance_identity_code'))
                // ->where('maintain_station_name', $request->get('maintain_station_name'))
                ->firstOrFail();

            $station_install_location_recode = StationInstallLocationRecord::with([])->where('entire_instance_identity_code', $entire_instance->identity_code)->first();
            if ($station_install_location_recode) {
                $station_install_location_recode
                    ->fill([
                        // 'maintain_station_unique_code' => $maintain->unique_code,
                        // 'maintain_station_name' => $maintain->name,
                        'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                        'crossroad_number' => $request->get('crossroad_number') ?? '',
                        'open_direction' => $request->get('open_direction') ?? '',
                        'is_indoor' => 1,
                        'section_unique_code' => '',
                        'processor_id' => session('account.id'),
                        'entire_instance_identity_code' => $entire_instance->identity_code,
                    ])
                    ->saveOrFail();
            } else {
                $station_install_location_recode = StationInstallLocationRecord::with([])->create([
                    // 'maintain_station_unique_code' => $maintain->unique_code,
                    // 'maintain_station_name' => $maintain->name,
                    'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                    'crossroad_number' => $request->get('crossroad_number') ?? '',
                    'open_direction' => $request->get('open_direction') ?? '',
                    'is_indoor' => 1,
                    'section_unique_code' => '',
                    'processor_id' => session('account.id'),
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                ]);
            }

            $entire_instance->fill([
                // 'maintain_station_name' => $maintain->name,
                'maintain_location_code' => $request->get('maintain_location_code') ?? '',
                'crossroad_number' => $request->get('crossroad_number') ?? '',
                'open_direction' => $request->get('open_direction') ?? '',
            ]);
            DB::commit();

            $last3 = StationInstallLocationRecord::with(['Station'])
                ->orderByDesc('updated_at')
                ->limit(3)
                ->where('processor_id', session('account.id'))
                ->get();

            return JsonResponseFacade::created(['station_install_location_recode' => $station_install_location_recode, 'last3' => $last3], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("{$request->get('maintain_station_name')}?????????????????????");
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????(????????????)
     * ????????????????????????????????????
     * ???????????????
     */
    final public function getTaskStationCheckStatisticForProject(): JsonResponse
    {
        try {
            list($year, $month) = explode('-', request('expiring_at', date('Y-m')) ?? date('Y-m'));
            $origin_at = Carbon::parse("{$year}-{$month}-1")->startOfMonth()->format('Y-m-d 00:00:00');
            $finish_at = Carbon::parse("{$year}-{$month}-1")->endOfMonth()->format('Y-m-d 23:59:59');
            $maintain = Maintain::with(['Parent'])->where('unique_code', request('maintain_unique_code'))->first();

            // ????????????
            $mission_statistic = DB::table('task_station_check_entire_instances as tscei')
                ->selectRaw("count(tscei.entire_instance_identity_code) as aggregate, cpro.name as project_name, cpro.id as project_id, cpro.type as project_type, 'mission' as type")
                ->join(DB::raw('task_station_check_orders tsco'), 'tsco.serial_number', '=', 'tscei.task_station_check_order_sn')
                ->join(DB::raw('check_plans cp'), 'cp.serial_number', '=', 'tsco.check_plan_serial_number')
                ->join(DB::raw('check_projects cpro'), 'cpro.id', '=', 'cp.check_project_id')
                ->whereBetween('tsco.expiring_at', [$origin_at, $finish_at])
                ->where(function ($query) {
                    $query
                        ->where('tsco.principal_id_level_1', session('account.id'))
                        ->orWhere('tsco.principal_id_level_2', session('account.id'))
                        ->orWhere('tsco.principal_id_level_3', session('account.id'))
                        ->orWhere('tsco.principal_id_level_4', session('account.id'))
                        ->orWhere('tsco.principal_id_level_5', session('account.id'));
                })
                ->when(
                    !empty(request('project_id')),
                    function ($query) {
                        $query->where('cpro.id', request('project_id'));
                    }
                )
                ->when(
                    !empty(request('project_type')),
                    function ($query) {
                        $query->where('cpro.type', request('project_type'));
                    }
                )
                ->when(
                    empty($maintain),
                    function ($query) {
                        // ?????????????????????????????????
                        $query
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 'tsco.scene_workshop_unique_code')
                            ->addSelect(['sc.name', 'sc.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain),
                    function ($query) use ($maintain) {
                        switch ($maintain->type) {
                            case '????????????':
                                // ???????????????????????????????????????
                                $query
                                    ->where('tsco.scene_workshop_unique_code', $maintain->unique_code)
                                    ->join(DB::raw('maintains s'), 's.unique_code', '=', 'tsco.maintain_station_unique_code')
                                    ->addSelect(['s.name', 's.unique_code']);
                                break;
                            case '??????':
                                // ???????????????????????????5??????????????????
                                $query
                                    ->join(DB::raw('accounts as a'), 'a.id', '=', 'tsco.principal_id_level_5')
                                    ->where('tsco.maintain_station_unique_code', $maintain->unique_code)
                                    ->addSelect(['a.nickname as name', 'a.id as unique_code']);
                                break;
                        }
                    }
                )
                ->groupBy(['project_name', 'project_id', 'name', 'unique_code', 'project_type', 'type',])
                ->get();

            // ????????????
            $finish_statistic = DB::table('task_station_check_entire_instances as tscei')
                ->selectRaw("count(tscei.entire_instance_identity_code) as aggregate, cpro.name as project_name, cpro.id as project_id, cpro.type as project_type, 'finish' as type")
                ->join(DB::raw('task_station_check_orders as tsco'), 'tsco.serial_number', '=', 'tscei.task_station_check_order_sn')
                ->join(DB::raw('check_plans cp'), 'cp.serial_number', '=', 'tsco.check_plan_serial_number')
                ->join(DB::raw('check_projects cpro'), 'cpro.id', '=', 'cp.check_project_id')
                ->where('tscei.processor_id', '<>', 0)
                ->where('tscei.processed_at', '<>', null)
                ->whereBetween('tsco.expiring_at', [$origin_at, $finish_at])
                ->where(function ($query) {
                    $query
                        ->where('tsco.principal_id_level_1', session('account.id'))
                        ->orWhere('tsco.principal_id_level_2', session('account.id'))
                        ->orWhere('tsco.principal_id_level_3', session('account.id'))
                        ->orWhere('tsco.principal_id_level_4', session('account.id'))
                        ->orWhere('tsco.principal_id_level_5', session('account.id'));
                })
                ->when(
                    !empty(request('project_id')),
                    function ($query) {
                        $query->where('cpro.id', request('project_id'));
                    }
                )
                ->when(
                    !empty(request('project_type')),
                    function ($query) {
                        $query->where('cpro.type', request('project_type'));
                    }
                )
                ->when(
                    empty($maintain),
                    function ($query) {
                        // ?????????????????????????????????
                        $query
                            ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 'tsco.scene_workshop_unique_code')
                            ->addSelect(['sc.name', 'sc.unique_code']);
                    }
                )
                ->when(
                    !empty($maintain),
                    function ($query) use ($maintain) {
                        switch ($maintain->type) {
                            case '????????????':
                                // ???????????????????????????????????????
                                $query
                                    ->where('tsco.scene_workshop_unique_code', $maintain->unique_code)
                                    ->join(DB::raw('maintains s'), 's.unique_code', '=', 'tsco.maintain_station_unique_code')
                                    ->addSelect(['s.name', 's.unique_code']);
                                break;
                            case '??????':
                                // ???????????????????????????5??????????????????
                                $query
                                    ->join(DB::raw('accounts as a'), 'a.id', '=', 'tsco.principal_id_level_5')
                                    ->where('tsco.maintain_station_unique_code', $maintain->unique_code)
                                    ->addSelect(['a.nickname as name', 'a.id as unique_code']);
                                break;
                        }
                    }
                )
                ->groupBy(['project_name', 'project_id', 'name', 'unique_code', 'project_type', 'type',])
                ->get();

            $statistics = [];

            return JsonResponseFacade::data([
                // 'plan_statistic' => $plan_statistic,
                'mission_statistic' => $mission_statistic,
                'finish_statistic' => $finish_statistic,
            ]);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????????
     */
    final public function getTaskStationCheckOrders(): JsonResponse
    {
        try {
            switch (session('account.rank')->code ?? null) {
                case 'SceneWorkAreaPrincipal':
                    $current_work_area_unique_code = session('account.work_area_unique_code');
                    if (!$current_work_area_unique_code) return JsonResponseFacade::errorForbidden('????????????????????????');

                    $origin_at = now()->startOfMonth()->format('Y-m-d 00:00:00');
                    $finish_at = now()->endOfMonth()->format('Y-m-d 23:59:59');

                    $list = DB::table('task_station_check_entire_instances as tscei')
                        ->selectRaw("DATE_FORMAT(tscei.processed_at, '%d') as processed_at,count(tscei.entire_instance_identity_code) as count")
                        ->join(DB::raw('task_station_check_orders tsco'), 'tscei.task_station_check_order_sn', '=', 'tsco.serial_number')
                        ->where('tsco.work_area_unique_code', $current_work_area_unique_code)
                        ->whereBetween('tscei.processed_at', [$origin_at, $finish_at])
                        ->groupBy(['tscei.processed_at'])
                        ->get();
                    $total = DB::table('task_station_check_orders')->selectRaw('sum(number) as total')->whereBetween('expiring_at', [$origin_at, $finish_at])->value('total');
                    $data = [
                        'info' => [
                            'work_area_name' => DB::table('work_areas')->where('unique_code', $current_work_area_unique_code)->value('name'),
                            'total' => $total,
                        ],
                        'list' => $list
                    ];
                    return JsonResponseFacade::data($data);
                    break;
                default:
                    $task_station_check_orders = ModelBuilderFacade::init(
                        request(),
                        TaskStationCheckOrder::with([
                            'TaskStationCheckEntireInstances',
                            'PrincipalIdLevel1',
                            'PrincipalIdLevel2',
                            'PrincipalIdLevel3',
                            'PrincipalIdLevel4',
                            'PrincipalIdLevel5',
                            'MaintainStation',
                            'WithCheckPlan',
                            'WithCheckPlan.WithCheckProject',
                        ])
                            ->withCount('TaskStationCheckEntireInstances'),
                        [
                            'principal_id_level_1',
                            'principal_id_level_2',
                            'principal_id_level_3',
                            'principal_id_level_4',
                            'principal_id_level_5',
                        ]
                    )
                        ->extension(function ($TaskStationCheckOrder) {
                            return $TaskStationCheckOrder
                                ->where(function ($query) {
                                    $query
                                        ->where('principal_id_level_1', session('account.id'))
                                        ->orWhere('principal_id_level_2', session('account.id'))
                                        ->orWhere('principal_id_level_3', session('account.id'))
                                        ->orWhere('principal_id_level_4', session('account.id'))
                                        ->orWhere('principal_id_level_5', session('account.id'));
                                });
                        })
                        ->all();

                    return JsonResponseFacade::data(['task_station_check_orders' => $task_station_check_orders]);
                    break;
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????????????????
     * @param string $sn
     * @return JsonResponse
     */
    final public function getTaskStationCheckOrder(string $sn): JsonResponse
    {
        try {
            $task_station_check_order = TaskStationCheckOrder::with([
                'TaskStationCheckEntireInstances',
                'TaskStationCheckEntireInstances.EntireInstance',
                'TaskStationCheckEntireInstances.Processor',
                'PrincipalIdLevel1',
                'PrincipalIdLevel2',
                'PrincipalIdLevel3',
                'PrincipalIdLevel4',
                'PrincipalIdLevel5',
                'WithCheckPlan',
                'WithCheckPlan.WithCheckProject',
            ])
                ->where('serial_number', $sn)
                ->withCount(['TaskStationCheckEntireInstances' => function ($TaskStationCheckEntireInstances) {
                    return $TaskStationCheckEntireInstances->where('processor_id', '<>', 0)
                        ->where('processed_at', '<>', null);
                }])
                ->firstOrFail();

            return JsonResponseFacade::data(['task_station_check_order' => $task_station_check_order]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ???????????????????????????
     * @param Request $request
     * @return mixed
     */
    final public function postTaskStationCheckOrder(Request $request): JsonResponse
    {
        try {
            // ?????????????????????????????????
            // $principal1 = Account::with([])->where('id', $request->get('principal_id_1'))->where('rank', 'SectionChief')->first();
            $principal1 = Account::with([])->where('rank', 'SectionChief')->where('id', 4615)->orderByDesc('id')->first();
            if (!$principal1) return JsonResponseFacade::errorEmpty('????????????????????????????????????');
            // ??????????????????????????????????????????
            // $principal2 = Account::with([])->where('id', $request->get('principal_id_2'))->where('rank', 'EngineerMaster')->first();
            $principal2 = Account::with([])->where('rank', 'EngineerMaster')->where('workshop_unique_code', session('workshop_unique_code'))->orderByDesc('id')->first();
            if (!$principal2) return JsonResponseFacade::errorEmpty('?????????????????????????????????????????????');
            // ????????????????????????
            $principal5 = Account::with(['WorkAreaByUniqueCode', 'Workshop'])->where('id', $request->get('principal_id_5'))->first();
            if (!$principal5) return JsonResponseFacade::errorEmpty('???????????????????????????');
            if ($principal5->rank->code != 'SceneWorkAreaCrew') return JsonResponseFacade::errorForbidden("??????????????????????????????");
            if (!@$principal5->WorkAreaByUniqueCode) return JsonResponseFacade::errorEmpty('????????????????????????????????????', $principal5);
            if (!@$principal5->WorkAreaByUniqueCode->Workshop) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}??????????????????");
            // ????????????????????????
            $principal4 = Account::with([])->where('rank', 'SceneWorkAreaPrincipal')->where('work_area_unique_code', $principal5->WorkAreaByUniqueCode->unique_code)->get();
            if ($principal4->isEmpty()) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}??????????????????", $principal4);
            if ($principal4->count() > 1) return JsonResponseFacade::errorEmpty("{$principal5->WorkAreaByUniqueCode->name}???????????????", $principal4);
            $principal4 = $principal4->first();
            if (!session('account.workshop_unique_code')) return JsonResponseFacade::errorEmpty('??????????????????????????????');
            // ??????
            if (!$request->get('project')) return JsonResponseFacade::errorEmpty('??????????????????');
            // ????????????
            if (!$request->get('expiring_at')) return JsonResponseFacade::errorEmpty('????????????????????????');
            $expiring_at = Carbon::parse($request->get('expiring_at'))->format('Y-m-d 00:00:00');
            // ??????
            $diff = [];
            $identity_codes = EntireInstance::with([])->whereIn('identity_code', $request->get('identity_codes'))->pluck('identity_code')->toArray();
            if (!$identity_codes) return JsonResponseFacade::errorForbidden("?????????????????????????????????<br>" . join("<br>", $identity_codes));
            $diff = array_diff($identity_codes, $request->get('identity_codes'));
            if (!empty($diff)) return JsonResponseFacade::errorForbidden("?????????????????????????????????<br>" . join("<br>", $diff));

            if ($request->get('check_plan_serial_number')) {
                // ????????????????????????
                $check_plan = CheckPlan::with([])->where('serial_number', $request->get('check_plan_serial_number'))->first();
                if (!$check_plan) return JsonResponseFacade::errorEmpty('???????????????????????????');

                // ????????????
                if (!$check_plan->station_unique_code) return JsonResponseFacade::errorEmpty('???????????????????????????');
                $station = Maintain::with([])->where('unique_code', $check_plan->station_unique_code)->first();
                if (!$station) return JsonResponseFacade::errorForbidden('??????????????????');

                // ????????????????????????
                $task_station_check_order = TaskStationCheckOrder::with([])->create([
                    'serial_number' => TaskStationCheckOrder::generateSerialNumber(session('account.workshop_unique_code')),
                    'work_area_unique_code' => $principal5->WorkAreaByUniqueCode->unique_code,  // ????????????
                    'scene_workshop_unique_code' => session('account.workshop_unique_code'),  // ????????????
                    'maintain_station_unique_code' => $check_plan->station_unique_code,  // ????????????
                    'principal_id_level_1' => $principal1->id,  // 1????????????????????????
                    'principal_id_level_2' => $principal2->id,  // 2?????????????????????????????????
                    'principal_id_level_3' => session('account.id'),  // 3????????????????????????????????????
                    'principal_id_level_4' => $principal4->id,  // 4????????????????????????????????????
                    'principal_id_level_5' => $principal5->id,  // 5????????????????????????????????????
                    'expiring_at' => $expiring_at,  // ????????????
                    'title' => "{$principal5->Workshop->name} {$principal5->WorkAreaByUniqueCode->name} {$principal5->nickname} " . date('Y-m-d', strtotime($expiring_at)),
                    'unit' => $request->get('unit'),  // ??????
                    'number' => count($request->get('identity_codes')),  // ????????????
                    'check_plan_serial_number' => $check_plan->serial_number,
                ]);

                // ????????????
                foreach ($request->get('identity_codes') as $identity_code) {
                    // ??????????????????????????????
                    TaskStationCheckEntireInstance::with([])->create([
                        'task_station_check_order_sn' => $task_station_check_order->serial_number,
                        'entire_instance_identity_code' => $identity_code,
                    ]);

                    // ??????????????????????????????is_use??????
                    CheckPlanEntireInstance::with([])
                        ->where('entire_instance_identity_code', $identity_code)
                        ->where('check_plan_serial_number', request('check_plan_serial_number'))
                        ->update(['updated_at' => now(), 'is_use' => 1]);
                }

                // ???????????????????????????????????????????????????????????????????????????????????????????????????
                $cpei = CheckPlanEntireInstance::with([])
                    ->select(['id'])
                    ->where('check_plan_serial_number', $request->get('check_plan_serial_number'))
                    ->get();
                if ($cpei->count() > 0) {
                    if ($cpei->count() == $cpei->where('is_use', 1)->count())
                        $check_plan->fill(['status' => 3])->saveOrFail();
                }
            } else {
                // ?????????????????????????????????
                // ??????????????????????????????
                $check_project = CheckProject::with([])->where('name', $request->get('project'))->where('type', 1)->first();
                if (!$check_project) $check_project = CheckProject::with([])->create(['name' => $request->get('project'), 'type' => 1]);

                // ????????????
                if (!$request->get('station_unique_code')) return JsonResponseFacade::errorEmpty('??????????????????');
                $station = Maintain::with([])->where('unique_code', $request->get('station_unique_code'))->first();
                if (!$station) return JsonResponseFacade::errorForbidden('??????????????????');

                // ????????????????????????
                $check_plan = CheckPlan::with([])->create([
                    'serial_number' => CheckPlan::generateSerialNumber(session('account.workshop_unique_code')),
                    'status' => 3,
                    'check_project_id' => $check_project->id,
                    'station_unique_code' => $station->unique_code,
                    'unit' => $request->get('unit'),
                    'expiring_at' => $expiring_at,
                    'number' => count($request->get('identity_codes')),
                    'account_id' => session('account.id'),
                ]);

                // ????????????????????????
                $task_station_check_order = TaskStationCheckOrder::with([])->create([
                    'serial_number' => TaskStationCheckOrder::generateSerialNumber(session('account.workshop_unique_code')),
                    'work_area_unique_code' => $principal5->WorkAreaByUniqueCode->unique_code,  // ????????????
                    'scene_workshop_unique_code' => session('account.workshop_unique_code'),  // ????????????
                    'maintain_station_unique_code' => $request->get('station_unique_code'),
                    'principal_id_level_1' => $principal1->id,  // 1????????????????????????
                    'principal_id_level_2' => $principal2->id,  // 2?????????????????????????????????
                    'principal_id_level_3' => session('account.id'),  // 3????????????????????????????????????
                    'principal_id_level_4' => $principal4->id,  // 4????????????????????????????????????
                    'principal_id_level_5' => $principal5->id,  // 5????????????????????????????????????
                    'expiring_at' => $expiring_at,  // ????????????
                    'title' => "{$principal5->Workshop->name} {$principal5->WorkAreaByUniqueCode->name} {$principal5->nickname} " . date('Y-m-d', strtotime($expiring_at)),
                    'unit' => $request->get('unit'),  // ??????
                    'number' => count($request->get('identity_codes')),  // ????????????
                    'check_plan_serial_number' => $check_plan->serial_number,
                ]);

                // ????????????
                foreach ($request->get('identity_codes') as $identity_code) {
                    // ??????????????????????????????
                    CheckPlanEntireInstance::with([])->create([
                        'check_plan_serial_number' => $check_plan->serial_number,
                        'entire_instance_identity_code' => $identity_code,
                        'is_use' => 1,
                        'task_station_check_order_serial_number' => $task_station_check_order->serial_number,
                    ]);

                    // ??????????????????????????????
                    TaskStationCheckEntireInstance::with([])->create([
                        'task_station_check_order_sn' => $task_station_check_order->serial_number,
                        'entire_instance_identity_code' => $identity_code,
                    ]);
                }
            }

            return JsonResponseFacade::created(['task_station_check_order' => $task_station_check_order], '??????????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @return mixed
     */
    final public function getTaskStationCheckEntireInstances(): JsonResponse
    {
        try {
            $task_station_check_entire_instances = ModelBuilderFacade::init(
                request(),
                TaskStationCheckEntireInstance::with(['TaskStationCheckOrder']),
                ['maintain_station_unique_code']
            )
                ->extension(function ($query) {
                    return $query->when(request('maintain_station_unique_code'), function ($query) {
                        $query->whereHas('TaskStationCheckOrder', function ($TaskStationCheckOrder) {
                            $TaskStationCheckOrder->where('maintain_station_unique_code', request('maintain_station_unique_code'));
                        });
                    });
                })
                ->all();

            return JsonResponseFacade::data(['task_station_check_entire_instances' => $task_station_check_entire_instances]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param string $sn
     * @return mixed
     * @throws Throwable
     */
    final public function postTaskStationCheckEntireInstance(Request $request, string $sn): JsonResponse
    {
        try {
            // ????????????
            $task_station_check_order = TaskStationCheckOrder::with([])->where('serial_number', $sn)->first();
            if (!$task_station_check_order) return JsonResponseFacade::errorEmpty('???????????????????????????');

            // ????????????
            $entire_instance = EntireInstance::with([])->select(['id'])->where('identity_code', $request->get('entire_instance_identity_code'))->first();
            if (!$entire_instance) return JsonResponseFacade::errorEmpty('???????????????');

            // ????????????
            $task_station_check_entire_instance = TaskStationCheckEntireInstance::with([])
                ->where('task_station_check_order_sn', $sn)
                ->where('entire_instance_identity_code', $request->get('entire_instance_identity_code'))
                ->first();
            if (!$task_station_check_entire_instance) return JsonResponseFacade::errorEmpty('???????????????');
            $task_station_check_entire_instance->fill(
                array_merge($request->all(), [
                    'task_station_check_order_sn' => $sn,
                    'processor_id' => session('account.id'),
                    'processed_at' => date('Y-m-d H:i:s'),
                ])
            )
                ->saveOrFail();

            // ??????????????????
            EntireInstanceLogFacade::makeOne(
                '????????????',
                $request->get('entire_instance_identity_code'),
                2,
                '',
                session('account.nickname')
                . "?????????????????????{$task_station_check_order->project} "
                . '<a href="javascript:" onclick="fnShowTaskStationCheckEntireInstanceImages(' . $task_station_check_entire_instance->id . ')">????????????????????????</a>'
            );

            // ????????????????????????
            $task_station_check_order = TaskStationCheckOrder::with(['TaskStationCheckEntireInstances'])
                ->withCount(['TaskStationCheckEntireInstances' => function ($TaskStationEntireInstances) {
                    return $TaskStationEntireInstances->where('processor_id', '<>', 0)
                        ->where('processed_at', '<>', null);
                }])
                ->where('serial_number', $sn)
                ->first();
            if ($task_station_check_order->task_station_check_entire_instances_count >= $task_station_check_order->number)
                $task_station_check_order->fill(['updated_at' => now(), 'status' => 'DONE', 'finished_at' => now()])->saveOrFail();

            // ????????????????????????
            $task_station_check_orders_same_plan_done_count = TaskStationCheckOrder::with([])
                ->where('check_plan_serial_number', $task_station_check_order->check_plan_serial_number)
                ->where('status', 'DONE')
                ->count('id');
            $task_station_check_orders_same_plan_undone_count = TaskStationCheckOrder::with([])
                ->where('check_plan_serial_number', $task_station_check_order->check_plan_serial_number)
                ->where('status', 'UNDONE')
                ->count('id');
            if (($task_station_check_orders_same_plan_done_count == $task_station_check_orders_same_plan_undone_count) && ($task_station_check_orders_same_plan_undone_count > 0)) {
                // ????????????????????????
                CheckPlan::with([])->where('serial_number', $task_station_check_order->check_plan_serial_number)->update(['updated_at' => now(), 'status' => 1]);
            }

            return JsonResponseFacade::updated([
                'finished_at' => date('Y-m-d H:i:s'),
                'task_station_check_order' => $task_station_check_order,
                'task_station_check_entire_instance' => $task_station_check_entire_instance
            ], '????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param string $sn
     * @return mixed
     */
    final public function deleteTaskStationCheckEntireInstance(Request $request, string $sn): JsonResponse
    {
        try {
            TaskStationCheckEntireInstance::with([])
                ->where('task_station_check_order_sn', $sn)
                ->where('entire_instance_identity_code', $request->get('entire_instance_identity_code'))
                ->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ????????????????????????
     * @param string $sn
     * @return mixed
     */
    final public function deleteTaskStationCheckOrder(string $sn): JsonResponse
    {
        try {
            // ????????????
            $task_station_check_order = TaskStationCheckOrder::with(['TaskStationCheckEntireInstances'])->where('serial_number', $sn)->firstOrFail();
            if ($task_station_check_order->TaskStationCheckEntireInstances->isNotEmpty()) return JsonResponseFacade::errorForbidden('???????????????????????????????????????');

            $task_station_check_order->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????????????????????
     * @param string $serial_number
     * @return mixed
     */
    final public function getCheckPlan(string $serial_number = ''): JsonResponse
    {
        try {
            if ($serial_number) {
                $check_plan = CheckPlan::with([
                    'WithAccount',
                    'WithCheckProject',
                    'WithStation',
                    'CheckPlanEntireInstances',
                    'CheckPlanEntireInstances.EntireInstance',
                ])
                    ->where('serial_number', $serial_number)
                    ->firstOrFail();
                return JsonResponseFacade::data(['check_plan' => $check_plan]);
            } else {
                $check_plans = ModelBuilderFacade::init(
                    request(),
                    CheckPlan::with([
                        'WithAccount',
                        'WithCheckProject',
                        'WithStation',
                        'CheckPlanEntireInstances',
                        'CheckPlanEntireInstances.EntireInstance',
                    ])
                )
                    ->all();
                return JsonResponseFacade::data(['check_plans' => $check_plans]);
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????? ????????????????????????
     * @return JsonResponse
     */
    final public function getEntireInstanceForCreateTaskStationCheckOrder(): JsonResponse
    {
        try {
            $entire_instances = ModelBuilderFacade::init(
                request(),
                EntireInstance::with([
                    'Category',
                    'EntireModel',
                    'PartModel',
                ])
            )
                ->extension(function ($builder) {
                    return $builder->where('crossroad_number', '<>', '');
                })
                ->all()
                ->groupBy(['crossroad_number']);

            return JsonResponseFacade::data(['entire_instances' => $entire_instances]);
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ?????????????????????????????????????????????
     */
    final public function getCheckProject(int $id = 0)
    {
        try {
            if ($id > 0) {
                $check_project = ModelBuilderFacade::init(request(), CheckProject::with([]))
                    ->extension(function ($builder) use ($id) {
                        return $builder->where('id', $id);
                    })
                    ->firstOrFail();
                return JsonResponseFacade::data(['check_project' => $check_project]);
            } else {
                $check_projects = ModelBuilderFacade::init(request(), CheckProject::with([]))->all();
                return JsonResponseFacade::data(['check_projects' => $check_projects]);
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * ???????????????????????????????????????
     * @return mixed
     */
    final public function getInstallPositionsByInstallTierUniqueCode(string $install_tier_unique_code)
    {
        try {
            $installTier = InstallTier::with([
                'WithInstallPositions' => function ($query) {
                    return $query->select('unique_code', 'name', 'install_tier_unique_code', 'volume');
                },
                'WithInstallShelf',
            ])->where('unique_code', $install_tier_unique_code)->firstOrFail();

            return JsonResponseFacade::data([
                'workshop_name' => $installTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->Parent->name,
                'station_name' => $installTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name,
                'room_name' => $installTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text,
                'platoon_name' => $installTier->WithInstallShelf->WithInstallPlatoon->name,
                'shelf_name' => $installTier->WithInstallShelf->name,
                'tier_name' => $installTier->name,
                'positions' => $installTier->WithInstallPositions->toArray()
            ], '????????????????????????');
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * get repair base breakdown orders
     * @return JsonResponse
     */
    final public function getRepairBaseBreakdownOrders(): JsonResponse
    {
        try {
            $repair_base_breakdown_orders = ModelBuilderFacade::init(
                request(),
                RepairBaseBreakdownOrder::with([
                    'Processor',
                ])
            )
                ->all();

            return JsonResponseFacade::dict(['repair_base_breakdown_orders' => $repair_base_breakdown_orders,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * get repair base breakdown order detail
     * @param string $serial_number
     * @return JsonResponse
     */
    final public function getRepairBaseBreakdownOrder(string $serial_number): JsonResponse
    {
        try {
            $repair_base_breakdown_order = ModelBuilderFacade::init(
                request(),
                RepairBaseBreakdownOrder::with([
                    'Processor',
                ])
            )
                ->extension(function ($builder) use ($serial_number) {
                    $builder->where('serial_number');
                })
                ->firstOrFail();

            return JsonResponseFacade::dict(['repair_base_breakdown_order' => $repair_base_breakdown_order]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * store repair base breakdown order
     * @param Request $request
     * @return JsonResponse
     */
    final public function postRepairBaseBreakdownOrder(Request $request): JsonResponse
    {
        try {
            $work_area_unique_code = session('account.work_area_unique_code');
            if (!$work_area_unique_code) return JsonResponseFacade::errorForbidden('????????????????????????');
            $work_area_id = intval(substr($work_area_unique_code, 5));

            $identity_codes = array_pluck($request->get('entire_instances'), 'identity_code');
            if (!$identity_codes) return JsonResponseFacade::errorValidate('??????????????????????????????');

            $entire_instances = EntireInstance::with([])->whereIn('identity_code', $identity_codes);
            $diff = array_diff($entire_instances->pluck('identity_code')->toArray(), $identity_codes);
            if ($diff) return JsonResponseFacade::errorForbidden("?????????????????????????????????" . implode(',', $diff));

            $station_breakdowns = [];
            foreach ($request->get('entire_instances') as $item) {
                [
                    'identity_code' => $identity_code,
                    'station_breakdown_explain' => $station_breakdown_explain,
                    'station_breakdown_submitted_at' => $station_breakdown_submitted_at,
                    'station_breakdown_submitter_name' => $station_breakdown_submitter_name,
                ] = $item;
                $station_breakdowns[$identity_code] = [
                    'station_breakdown_explain' => $station_breakdown_explain,
                    'station_breakdown_submitted_at' => $station_breakdown_submitted_at,
                    'station_breakdown_submitter_name' => $station_breakdown_submitter_name,
                ];
            }

            DB::beginTransaction();
            // ??????????????????????????????
            $breakdown_order_in = new RepairBaseBreakdownOrder();
            $breakdown_order_in->fill([
                'serial_number' => $new_breakdown_in_sn = CodeFacade::makeSerialNumber('BREAKDOWN_IN'),
                'work_area_id' => $work_area_id,
                'status' => 'DONE',
                'direction' => 'IN',
                'processor_id' => session('account.id'),
                'processed_at' => now(),
            ]);
            $breakdown_order_in->saveOrFail();

            // ??????????????????????????????
            $breakdown_order_out = new RepairBaseBreakdownOrder();
            $breakdown_order_out->fill([
                'serial_number' => $new_breakdown_out_sn = CodeFacade::makeSerialNumber('BREAKDOWN_OUT'),
                'work_area_id' => $work_area_id,
                'status' => 'UNDONE',
                'direction' => 'OUT',
                'in_sn' => $new_breakdown_in_sn,
            ]);
            $breakdown_order_out->saveOrFail();

            // ????????????
            $in_warehouse_sn = WarehouseReportFacade::batchInWithEntireInstanceIdentityCodes(
                $identity_codes,
                session('account.id'),
                now()->format('Y-m-d H:i:s'),
                $request->get('connectionName'),
                $request->get('connectionPhone')
            );

            $entire_instances->each(function (EntireInstance $entire_instance)
            use ($new_breakdown_in_sn, $new_breakdown_out_sn, $station_breakdowns, $in_warehouse_sn) {
                RepairBaseBreakdownOrderEntireInstance::with([])
                    ->create([
                        'old_entire_instance_identity_code' => $entire_instance->identity_code,
                        'new_entire_instance_identity_code' => '',
                        'maintain_location_code' => @$entire_instance->last_maintain_location_code ?: (@$entire_instance->maintain_location_code ?: ''),
                        'crossroad_number' => @$entire_instance->last_crossroad_number ?: (@$entire_instance->crossroad_number),
                        'traction' => @$entire_instance->traction ?: '',
                        'open_direction' => @$entire_instance->last_open_direction ?: (@$entire_instance->open_direction ?: ''),
                        'said_rod' => @$entire_instance->said_rod ?: '',
                        'crossroad_type' => @$entire_instance->crossroad_type ?: '',
                        'point_switch_group_type' => @$entire_instance->point_switch_group_type ?: '',
                        'extrusion_protect' => @$entire_instance->extrusion_protect ?: '',
                        'scene_workshop_name' => @$entire_instance->maintain_workshop_name ?: '',
                        'maintain_station_name' => @$entire_instance->maintain_station_name ?: '',
                        'in_sn' => $new_breakdown_in_sn,
                        'out_sn' => $new_breakdown_out_sn,
                        'in_warehouse_sn' => $in_warehouse_sn,
                    ]);

                // ??????????????????
                BreakdownLogFacade::createStation(
                    @$entire_instance ?? '',
                    @$station_breakdowns[$entire_instance->identity_code]['station_breakdown_explain'] ?? '',
                    @$station_breakdowns[$entire_instance->identity_code]['station_submitted_at'] ?? '',
                    @$station_breakdowns[$entire_instance->identity_code]['station_breakdown_crossroad_number'] ?? ''
                );
            });
            DB::commit();

            return JsonResponseFacade::dict([
                'repair_base_breakdown_order_in' => $breakdown_order_in,
                'repair_base_breakdown_order_out' => $breakdown_order_out,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????????????????
     * @param string $identity_code
     * @return mixed
     */
    final public function getEntireInstanceForBind(string $identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartInstances',
                'PartInstances.Category',
                'PartInstances.EntireModel',
                'PartInstances.SubModel',
                'PartInstances.PartModel',
                'PartInstances.PartCategory',
            ])
                ->where('is_part', false)
                ->where('identity_code', $identity_code)
                ->firstOrFail();

            return JsonResponseFacade::dict([
                'entire_instance' => $entire_instance,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("???????????????????????????{$identity_code}");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????????????????
     * @param string $identity_code
     * @return mixed
     */
    final public function getPartInstanceForBind(string $identity_code)
    {
        try {
            $part_instance = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel',
                'PartCategory',
            ])
                ->where('is_part', true)
                ->where('identity_code', $identity_code)
                ->firstOrFail();
            if ($part_instance->entire_instance_identity_code) return JsonResponseFacade::errorValidate('????????????????????????');

            return JsonResponseFacade::dict([
                'part_instance' => $part_instance,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @param string $entire_instance_identity_code
     */
    final public function postBindPartInstances(Request $request, string $entire_instance_identity_code)
    {
        DB::beginTransaction();
        try {

            if (!DB::table('entire_instances as ei')
                ->whereNull('ei.deleted_at')
                ->where('ei.is_part', false)
                ->where('identity_code', $entire_instance_identity_code)
                ->exists()
            ) return JsonResponseFacade::errorValidate("?????????????????????{$entire_instance_identity_code}");

            $part_instance_identity_codes = $request->get('part_instance_identity_codes');
            if (!$part_instance_identity_codes) return JsonResponseFacade::errorValidate('????????????????????????');
            $diff = [];
            $diff = array_diff($part_instance_identity_codes, DB::table('entire_instances')->select(['identity_code'])->whereNull('deleted_at')->whereIn('identity_code', $part_instance_identity_codes)->where('is_part', true)->get()->pluck('identity_code')->toArray());
            if ($diff) return JsonResponseFacade::errorValidate('???????????????????????????' . implode(',', $diff));

            $already_part_instance_identity_codes = DB::table('entire_instances as ei')
                ->select(['identity_code'])
                ->whereNull('deleted_at')
                ->whereIn('identity_code', $part_instance_identity_codes)
                ->where('is_part', true)
                ->where('ei.entire_instance_identity_code', $entire_instance_identity_code)
                ->get()
                ->pluck('identity_code')
                ->toArray();
            $update_data = [];
            $entire_instance_logs = [];
            $part_instance_logs = [];
            foreach ($part_instance_identity_codes as $part_instance_identity_code) {
                if (!in_array($part_instance_identity_code, $already_part_instance_identity_codes)) {
                    $update_data[] = $part_instance_identity_code;

                    // ??????????????????
                    $entire_instance_logs[] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'name' => '????????????',
                        'description' => "???????????????{$entire_instance_identity_code}???????????????" . session('account.nickname'),
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'type' => 7,
                        'url' => '',
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.nickname'),
                        'station_unique_code' => '',
                    ];

                    $part_instance_logs[] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'name' => '????????????',
                        'description' => "???????????????{$entire_instance_identity_code}????????????{$entire_instance_identity_code}???????????????" . session('account.nickname'),
                        'entire_instance_identity_code' => $part_instance_identity_code,
                        'type' => 7,
                        'url' => '',
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.nickname'),
                        'station_unique_code' => '',
                    ];
                }
            }

            if ($update_data) {
                EntireInstance::with([])
                    ->where('is_part', true)
                    ->whereIn('identity_code', $update_data)
                    ->update([
                        'updated_at' => now(),
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                    ]);

                // ????????????
                EntireInstanceLog::with([])->insert($entire_instance_logs);  // ????????????
                EntireInstanceLog::with([])->insert($part_instance_logs);  // ????????????
            }
            DB::commit();

            $part_instances = EntireInstance::with([
                'Category',
                'EntireModel',
                'SubModel',
                'PartModel',
                'PartCategory',
            ])
                ->where('entire_instance_identity_code', $entire_instance_identity_code)
                ->where('is_part', true)
                ->get();

            return JsonResponseFacade::created(['part_instances' => $part_instances,], "???????????????" . count($update_data) . '?????????');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            DB::rollBack();
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ????????????
     * @param Request $request
     * @param string $identity_code
     * @return mixed
     */
    final public function deleteUnbindPartInstance(Request $request, string $identity_code)
    {
        DB::beginTransaction();
        try {
            $part_instance = EntireInstance::with([])
                ->where('identity_code', $identity_code)
                ->where('is_part', true)
                ->firstOrFail();
            if (!$part_instance->entire_instance_identity_code) return JsonResponseFacade::errorForbidden('??????????????????????????????');
            $entire_instance_identity_code = $part_instance->entire_instance_identity_code;

            $part_instance->fill(['entire_instance_identity_code' => ''])->saveOrFail();

            /**
             * ????????????
             */
            EntireInstanceLog::with([])->create([
                'name' => '????????????',
                'description' => "???????????????{$identity_code}???????????????" . session('account.nickname'),
                'entire_instance_identity_code' => $entire_instance_identity_code,
                'type' => 8,
                'url' => '',
                'material_type' => 'ENTIRE',
                'operator_id' => session('account.nickname'),
                'station_unique_code' => '',
            ]);

            /**
             * ????????????
             */
            EntireInstanceLog::with([])->create([
                'name' => '????????????',
                'description' => "?????????{$entire_instance_identity_code}??????????????????{$identity_code}???????????????" . session('account.nickname'),
                'entire_instance_identity_code' => $identity_code,
                'type' => 8,
                'url' => '',
                'material_type' => 'ENTIRE',
                'operator_id' => session('account.nickname'),
                'station_unique_code' => '',
            ]);

            DB::commit();

            return JsonResponseFacade::deleted([], '????????????');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return JsonResponseFacade::errorEmpty('?????????????????????????????????????????????');
        } catch (Throwable $e) {
            DB::rollBack();
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ????????????????????????
     * @param Request $request
     * @param string $entire_instance_identity_code
     * @return mixed
     */
    final public function postBindInstallPosition(Request $request, string $entire_instance_identity_code)
    {
        try {
            $install_position_unique_code = $request->get('maintain_location_code');
            if (!$install_position_unique_code) return JsonResponseFacade::errorEmpty('????????????????????????');

            if (CodeFacade::isIdentityCode($entire_instance_identity_code)) {
                if (!EntireInstance::with([])->where("identity_code", $entire_instance_identity_code)->exists()) return JsonResponseFacade::errorEmpty('?????????????????????');
            } else {
                $entire_instance_count = EntireInstance::with([])->where('serial_number', $entire_instance_identity_code)->count();
                if ($entire_instance_count > 1) return JsonResponseFacade::errorEmpty("??????????????????????????????");
            }

            $install_position = InstallPosition::with([
                "WithInstallTier",
                "WithInstallTier.WithInstallShelf",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation",
                "WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation.Parent",
            ])
                ->where('unique_code', $install_position_unique_code)
                ->first();
            if (!$install_position) return JsonResponseFacade::errorEmpty('?????????????????????');

            $entire_instance = EntireInstance::with([])
                ->where("identity_code", $entire_instance_identity_code)
                ->first();
            $entire_instance->fill([
                'maintain_location_code' => $install_position_unique_code,
                'maintain_station_name' => $install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name,
                'maintain_workshop_name' => $install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->Parent->name,
                'status' => 'INSTALLED',
            ]);
            if ($request->get("serial_number")) {
                if (DB::table("entire_instances")->whereNull("deleted_at")->where("identity_code", "<>", $entire_instance_identity_code)->where("serial_number", $request->get("serial_number"))->exists()) {
                    return JsonResponseFacade::errorValidate("??????????????????????????????");
                }
                $entire_instance->fill([
                    "serial_number" => $request->get("serial_number"),
                ]);
            }
            $entire_instance->saveOrFail();

            return JsonResponseFacade::created([], '????????????????????????');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ????????????/???/??????
     * @param Request $request
     * @return mixed
     */
    final public function getInstallShelfWithIndex(Request $request)
    {
        try {
            $station_unique_code = $request->get('station_unique_code', '');
            $data = DB::table('install_shelves as i')
                ->selectRaw("CONCAT(case ir.type when 10 then '?????????' when 11 then '?????????' when 12 then '?????????' when 13 then '???????????????' when 14 then '?????????' when 15 then '?????????' when 16 then '???????????????' when 17 then 'SAM????????????' when 18 then 'SAM?????????' end, '-' ,ip.name ,'???' ,'-' ,i.name)  as name, i.unique_code")
                ->join(DB::raw('install_platoons ip'), 'i.install_platoon_unique_code', '=', 'ip.unique_code')
                ->join(DB::raw('install_rooms ir'), 'ip.install_room_unique_code', '=', 'ir.unique_code')
                ->where('ir.station_unique_code', $station_unique_code)
                ->get();
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * ????????????/???/???
     * @param Request $request
     * @return mixed
     */
    final public function getInstallPositionWithIndex(Request $request)
    {
        try {
            $install_shelf_unique_code = $request->get('install_shelf_unique_code');

            $data = InstallShelf::with([
                'install_tiers' => function ($install_tier) {
                    $install_tier->select('name', 'unique_code', 'install_shelf_unique_code')->orderByDesc('id');
                },
                'install_tiers.install_positions' => function ($install_positions) {
                    $install_positions->select('name', 'unique_code', 'install_tier_unique_code', 'volume');
                },
                'install_tiers.install_positions.EntireInstances' => function ($EntireInstance) {
                    $EntireInstance->select('identity_code', 'maintain_location_code', 'model_name');
                },
            ])
                ->select('name', 'unique_code')
                ->where('unique_code', $install_shelf_unique_code)
                ->firstOrFail();
            return JsonResponseFacade::data($data);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * ???????????????????????????????????????????????????
     * @param string $entire_instance_identity_code
     * @return mixed
     */
    final public function getFixWorkflows(string $entire_instance_identity_code)
    {
        try {
            $entire_instance = EntireInstance::with([])
                ->where('identity_code', $entire_instance_identity_code)
                ->first();
            if (!$entire_instance) return JsonResponseFacade::errorEmpty('?????????????????????');

            $ret_data = [];

            FixWorkflowProcess::with(['FixWorkflow'])
                ->whereHas('FixWorkflow', function ($FixWorkflow) use ($entire_instance_identity_code) {
                    $FixWorkflow->where('entire_instance_identity_code', $entire_instance_identity_code);
                })
                ->where('check_type', 'JSON2')
                ->each(function ($fix_workflow_process) use ($entire_instance_identity_code, &$ret_data) {
                    $upload_url = $fix_workflow_process->upload_url;
                    if (!file_exists(public_path($upload_url))) return null;

                    $ret_data[] = [
                        'processor_name' => $fix_workflow_process->Processor->nickname,
                        'created_at' => $fix_workflow_process->created_at,
                        'serial_number' => $fix_workflow_process->serial_number,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'stage' => $fix_workflow_process->stage,
                        'repair_body' => json_decode(file_get_contents(public_path($upload_url)), true)['body'],
                    ];
                });

            return JsonResponseFacade::dict(['fix_workflows' => $ret_data,]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * ???????????????????????????????????????????????????????????????
     * @param int $operator_id
     */
    final public function GetEntireInstanceLogsByOperatorId(int $operator_id)
    {
        $entire_instance_logs_with_month = @EntireInstanceLogFacade::GetLogsWithMonthByOperatorId($operator_id) ?: [];
        return JsonResponseFacade::dict(["entire_instance_logs" => $entire_instance_logs_with_month,]);
    }
}
