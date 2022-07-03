<?php

namespace App\Http\Controllers\V1;

use App\Facades\EntireInstanceFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\ThirdPartyRequest;
use App\Model\EntireInstance;
use App\Model\FixWorkflow;
use App\Model\ThirdParty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Jericho\TextHelper;
use Jericho\JwtHelper;

class ThirdPartyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * 登录
     * @param Request $request
     * @return Response
     */
    final public function login(Request $request): Response
    {
        try {
            # 表单验证
            $v = Validator::make($request->all(), ThirdPartyRequest::$RULES, ThirdPartyRequest::$MESSAGES);
            if ($v->fails()) return response()->make($v->errors()->first(), 421);

            # 验证密码
            $thirdParty = ThirdParty::where('username', $request->get('username'))->firstOrFail();
            if (!Hash::check($request->get('password'), $thirdParty->password)) return response()->make('账号或密码不匹配', 403);

            # 记录当日申请JWT次数
            $originTime = Carbon::today();
            $finishTime = Carbon::today()->modify('+1 day -1 second');
            $updatedAt = Carbon::parse($thirdParty->updated_at);
            if ($originTime <= $updatedAt && $finishTime >= $updatedAt) {
                # 当日内请求
                # 验证登陆次数
                if ($thirdParty->current_day_apply_for_times >= 3) return response()->make('当日申请JWT次数超过3次', 403);
                # 生成jwt
                $jwt = JwtHelper::INS()->make($thirdParty->username, ['username' => $thirdParty->username, 'open_id' => $thirdParty->open_id]);
                $thirdParty->fill(['jwt' => $jwt, 'current_day_apply_for_times' => $thirdParty->current_day_apply_for_times + 1])->saveOrFail();
            } else {
                # 次日请求
                # 生成jwt
                $jwt = JwtHelper::INS()->make($thirdParty->username, ['username' => $thirdParty->username, 'open_id' => $thirdParty->open_id]);
                $thirdParty->fill(['jwt' => $jwt, 'current_day_apply_for_times' => 1])->saveOrFail();
            }

            return response()->make($jwt);
        } catch (ModelNotFoundException $exception) {
            return response()->make(env('APP_DEBUG') ?
                '用户不存在：' . $exception->getMessage() :
                '用户不存在', 404);
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return response()->make(env('APP_DEBUG') ?
                "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                "意外错误", 500);
        }
    }

    /**
     * 操作密码
     * @param Request $request
     * @return Response
     */
    final public function password(Request $request): Response
    {
        try {
            $thirdParty = ThirdParty::where('open_id', $request->currentAccount->open_id)->firstOrFail();
            $thirdParty->fill(['password' => bcrypt($request->password)])->saveOrFail();

            # 记录当日申请JWT次数
            $originTime = Carbon::today();
            $finishTime = Carbon::today()->modify('+1 day -1 second');
            $updatedAt = Carbon::parse($thirdParty->updated_at);
            if ($originTime <= $updatedAt && $finishTime >= $updatedAt) {
                # 当日内请求
                # 验证登陆次数
                if ($thirdParty->current_day_apply_for_times >= 3) return response()->make('当日申请JWT次数超过3次', 403);
                # 生成jwt
                $jwt = JwtHelper::INS()->make($thirdParty->username, ['username' => $thirdParty->username, 'open_id' => $thirdParty->open_id]);
                $thirdParty->fill(['jwt' => $jwt, 'current_day_apply_for_times' => $thirdParty->current_day_apply_for_times + 1])->saveOrFail();
            } else {
                # 次日请求
                # 生成jwt
                $jwt = JwtHelper::INS()->make($thirdParty->username, ['username' => $thirdParty->username, 'open_id' => $thirdParty->open_id]);
                $thirdParty->fill(['jwt' => $jwt, 'current_day_apply_for_times' => 1])->saveOrFail();
            }

            return response()->make();
        } catch (ModelNotFoundException $exception) {
            $returnMessage = env('APP_DEBUG') ? '用户不存在：' . $exception->getMessage() : '用户不存在';
            return response()->make($returnMessage, 404);
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            $returnMessage = env('APP_DEBUG') ? "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" : "意外错误";
            return response()->make($returnMessage, 500);
        }
    }

    /**
     * 测试
     * @param Request $request
     * @return Response
     */
    final public function test(Request $request): Response
    {
        return response()->make(TextHelper::toJson($request->all()));
    }

    /**
     * 安装设备
     * @param Request $request
     * @param string $id
     * @return Response
     */
    final public function installS(Request $request, string $id): Response
    {
        try {
            $entireInstance = EntireInstance::where($request->get('type'), $id)->firstOrFail();
            # 计算下次周期修时间
            $req = array_merge($request->all(), \App\Facades\EntireInstanceFacade::nextFixingTime($entireInstance));
            $entireInstance->fill(array_merge($req, ['installed_at' => now()]))->saveOrFail();

            $entireInstance->fill($request->all())->saveOrFail();

            return response()->make();
        } catch (ModelNotFoundException $exception) {
            $returnMessage = env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在';
            return response()->make($returnMessage, 404);
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            $returnMessage = env('APP_DEBUG') ? "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" : "意外错误";
            return response()->make($returnMessage, 500);
        }
    }

    /**
     * 安装关键器件
     * @param Request $request
     * @param string $id
     * @return Response
     */
    final public function installQ(Request $request, string $id): Response
    {
        try {
            $entireInstance = EntireInstance::where($request->get('type'), $id)->firstOrFail();
            # 计算下次周期修时间
            // $req = array_merge($request->all(), EntireInstanceFacade::nextFixingTime($entireInstance));
            $entireInstance->fill(['installed_at' => now()])->saveOrFail();

            return response()->make();
        } catch (ModelNotFoundException $exception) {
            $returnMessage = env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在';
            return response()->make($returnMessage, 404);
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            $returnMessage = env('APP_DEBUG') ? "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" : "意外错误";
            return response()->make($returnMessage, 500);
        }
    }

    /**
     * 查询检修单历史
     * @param string $type
     * @param string $id
     * @return Response
     */
    final public function fixWorkflows(string $type, string $id): Response
    {
        # 当月起始时间
        $originTime = date('Y-m-01') . ' 00:00:00';
        $finishTime = Carbon::parse($originTime)->modify('+1 month')->toDateTimeString();

        try {
            $entireInstance = EntireInstance::where($type, $id)->firstOrFail();

            $date = explode('~', request('date', implode('~', [$originTime, $finishTime])));
            $fixWorkflows = FixWorkflow::with([
                'FixWorkflowProcesses',
                'FixWorkflowProcesses.PartInstance',
                'FixWorkflowProcesses.Measurement',
                'FixWorkflowProcesses.Processor' => function ($processor) {
                    $processor->select(['id', 'nickname']);
                },
                'EntireInstance',
                'EntireInstance.Category' => function ($category) {
                    $category->select(['name', 'unique_code']);
                },
                'EntireInstance.EntireModel' => function ($entireModel) {
                    $entireModel->select(['name', 'unique_code']);
                },
                'EntireInstance.PartInstances',
                'EntireInstance.PartInstances.PartModel',
            ])
                ->select([
                    'created_at',
                    'entire_instance_identity_code',
                    'status',
                    'serial_number',
                ])
                ->whereBetween('created_at', $date)
                ->where('entire_instance_identity_code', $entireInstance->identity_code)
                ->paginate();

            return response()->make($fixWorkflows->toJson());
        } catch (ModelNotFoundException $exception) {
            $returnMessage = env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在';
            return response()->make($returnMessage, 404);
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            $returnMessage = env('APP_DEBUG') ? "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" : "意外错误";
            return response()->make($returnMessage, 500);
        }
    }

    /**
     * 获取设备详情
     * @param string $type
     * @param string $id
     * @return Response
     */
    final public function entireInstance(string $type, string $id)
    {
        try {
            $entireInstance = EntireInstance::where($type, $id)->firstOrFail();

            return response()->make($entireInstance->toJson());
        } catch (ModelNotFoundException $exception) {
            $returnMessage = env('APP_DEBUG') ? '数据不存在：' . $exception->getMessage() : '数据不存在';
            return response()->make($returnMessage, 404);
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            $returnMessage = env('APP_DEBUG') ? "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" : "意外错误";
            return response()->make($returnMessage, 500);
        }
    }
}
