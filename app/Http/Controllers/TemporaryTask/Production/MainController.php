<?php

namespace App\Http\Controllers\TemporaryTask\Production;

use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireModel;
use App\Model\Maintain;
use App\Model\PartModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Jericho\BadRequestException;
use Jericho\CurlHelper;
use Jericho\TextHelper;

class MainController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'temporaryTask/production/main';
    private $_root_url = null;
    private $_auth = null;

    final public function __construct()
    {
        $this->_spas_protocol = env('SPAS_PROTOCOL');
        $this->_spas_url_root = env('SPAS_URL_ROOT');
        $this->_spas_port = env('SPAS_PORT');
        $this->_spas_api_root = env('SPAS_API_ROOT');
        $this->_spas_username = env('SPAS_USERNAME');
        $this->_spas_password = env('SPAS_PASSWORD');
        $this->_root_url = "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = ["Username:{$this->_spas_username}", "Password:{$this->_spas_password}"];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            # 获取与自己相关的任务列表
            $account_id = session('account.id');
            $account_ttpp = array_flip(Account::$TEMP_TASK_POSITIONS)[session('account.temp_task_position')];
            $account_ttpp_underline = TextHelper::hump2underline($account_ttpp);

            # 获取任务详情
            $main_task_response = CurlHelper::init([
//                'url' => "{$this->_root_url}/{$this->_spas_url}/byParagraph{$account_ttpp}Id",
                'url' => "{$this->_root_url}/{$this->_spas_url}/by{$account_ttpp}Id",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => [
                    strtolower($account_ttpp_underline) . '_id' => $account_id,
                    'paragraph_code' => env('ORGANIZATION_CODE'),
                    'page' => request('page', 1),
                ],
            ]);
            if ($main_task_response['code'] > 399) return back()->with('danger', $main_task_response['body']['message']);

            $main_tasks = new LengthAwarePaginator(
                $main_task_response['body']['data'],
                $main_task_response['body']['count'],
                $main_task_response['body']['limit'],
                $main_task_response['body']['current_page']
            );

            return view('TemporaryTask.Production.Main.index', [
                'main_tasks' => $main_tasks,
            ]);
        } catch (BadRequestException $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '数据中台链接失败');
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $main_task_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($main_task_id)
    {
        try {
            # 读取任务详情
            $main_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$main_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['message_id' => request('message_id')],
            ]);
            if ($main_task_response['code'] > 399) return redirect('/temporaryTask/production/main')->with('danger', '获取任务详情失败');

            # 读取任务留言板
            $main_task_note_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/noteByMainTaskId/{$main_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['page' => request('page', 1)],
            ]);

            # 获取子任务完成情况
            $sub_tasks_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/sub/byMainTaskId/{$main_task_id}",
                'method' => CurlHelper::GET,
            ]);
            if ($sub_tasks_response['code'] > 399) return redirect('temporaryTask/production/main')->with('danger', '获取子任务列表失败');

            $sub_tasks_status = [];
            $sub_task_count = count($sub_tasks_response['body']['data']);
            $sub_tasks = collect($sub_tasks_response['body']['data']);
            $sub_tasks_count = $sub_tasks->count();
            $sub_tasks_finish_count = $sub_tasks->where('status', 'FINISH')->count();
            $all_sub_task_is_finish = $sub_tasks_count > 0 && $sub_task_count == $sub_tasks_finish_count;
            foreach ($sub_tasks_response['body']['data'] as $key => $sub_task) {
                $sub_tasks_status[$sub_task['receiver_work_area_name']] = $sub_task['status_name'];
            }

            return view('TemporaryTask.Production.Main.show', [
                'main_task' => $main_task_response['body']['data'],
                'main_task_notes' => $main_task_note_response['body'],
                'all_sub_task_is_finish' => $all_sub_task_is_finish,
                'sub_tasks' => $sub_tasks_response['body']['data'],
                'sub_tasks_status' => $sub_tasks_status,
            ]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '数据中台链接失败');
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $main_task_id
     * @return \Illuminate\Http\Response
     */
    final public function edit($main_task_id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $main_task_id
     * @return \Illuminate\Http\Response
     */
    final public function update(Request $request, $main_task_id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $main_task_id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($main_task_id)
    {
        //
    }

    /**
     * 创建主任务留言
     * @param Request $request
     * @param int $main_task_id
     * @return mixed
     */
    final public function postNote(Request $request, int $main_task_id)
    {
        try {
            # 获取主任务信息
            $main_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$main_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            $receivers = [];
            $receivers['Section'] = ['id' => $main_task_response['body']['data']['initiator_id'], 'affiliation' => $main_task_response['body']['data']['initiator_affiliation']];
            if ($main_task_response['body']['data']['paragraph_principal_id']) $receivers['Principal'] = ['id' => $main_task_response['body']['data']['paragraph_principal_id'], 'affiliation' => $main_task_response['body']['data']['paragraph_code']];
            if ($main_task_response['body']['data']['paragraph_workshop_id']) $receivers['Workshop'] = ['id' => $main_task_response['body']['data']['paragraph_workshop_id'], 'affiliation' => $main_task_response['body']['data']['paragraph_code']];;
            if ($main_task_response['body']['data']['paragraph_monitoring_id']) $receivers['WorkArea'] = ['id' => $main_task_response['body']['data']['paragraph_monitoring_id'], 'affiliation' => $main_task_response['body']['data']['paragraph_code']];
            unset($receivers[array_flip(Account::$TEMP_TASK_POSITIONS)[session('account.temp_task_position')]]);
            $receivers2 = [];
            foreach ($receivers as $value) $receivers2[] = $value;


            # 创建留言
            $main_task_note_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/note",
                'headers' => $this->_auth,
                'method' => CurlHelper::POST,
                'contents' => [
                    'sender_id' => session('account.id'),  # 发件人
                    'sender_affiliation' => env('ORGANIZATION_CODE'),  # 发件人单位
                    'content' => $request->get('content'),  # 留言内容
                    'main_task_id' => $main_task_id,  # 主任务编号
                    'message_content' => TextHelper::strip($request->get('content'), true),  # 信息内容
                    'message_intro' => TextHelper::strip($request->get('content'), true),  # 消息预览
                    'message_receivers' => json_encode($receivers2),  # 消息接收人
                ]
            ]);
            return $main_task_note_response['body'];
            if ($main_task_note_response['code'] > 399) return response()->json($main_task_note_response['body'], $main_task_note_response['code']);

            return response()->json(['message' => '留言成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 删除主任务留言
     * @param Request $request
     * @param int $main_task_note_id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function deleteNote(Request $request, int $main_task_note_id)
    {
        try {
            $main_task_note_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/note/{$main_task_note_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::DELETE,
            ]);
            if ($main_task_note_response['code']) return response()->json($main_task_note_response['body'], $main_task_note_response['code']);

            return response()->json(['message' => '留言删除成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 电务段下达任务到车间主任 页面
     * @param int $main_task_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getMakeMainTask103(int $main_task_id)
    {
        try {
            # 获取主任务详情
            $main_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$main_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json(['message' => '获取任务信息失败'], $main_task_response['code']);

            # 获取人员列表
            $accounts = DB::table('accounts')
                ->where('id', '<>', session('account.id'))
                ->whereIn('temp_task_position', [
                    'Workshop',
                    'Engineer',
                    'WorkArea'
                ])
                ->pluck('nickname', 'id');

            return view('TemporaryTask.Production.Main.makeMainTask103_ajax', [
                'main_task' => $main_task_response['body']['data'],
                'accounts' => $accounts
            ]);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 电务段下达任务到车间主任
     * @param Request $request
     * @param int $main_task_id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postMakeMainTask103(Request $request, int $main_task_id)
    {
        try {
            # 任务阶段103
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/main/publish103/{$main_task_id}",
                'method' => CurlHelper::PUT,
                'contents' => [
                    'paragraph_workshop_id' => $request->get('paragraph_workshop_id'),
                    'stage_103_message' => TextHelper::strip($request->get('message', '')),
                    'stage_103_intro' => TextHelper::strip($request->get('message', ''), true),
                ],
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            return response()->json(['message' => '下达成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 指定盯控干部页面
     * @param int $main_task_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    final public function getMakeMainTask104(int $main_task_id)
    {
        try {
            # 获取主任务详情
            $main_task_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$main_task_id}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json(['message' => '获取任务信息失败'], $main_task_response['code']);

            # 获取人员列表
            $accounts = DB::table('accounts')
                ->where('id', '<>', session('account.id'))
                ->where('temp_task_position', 'Engineer')
                ->pluck('nickname', 'id');

            return view('TemporaryTask.Production.Main.makeMainTask104_ajax', [
                'main_task' => $main_task_response['body']['data'],
                'accounts' => $accounts
            ]);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 指定盯控干部
     * @param Request $request
     * @param int $main_task_id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postMakeMainTask104(Request $request, int $main_task_id)
    {
        try {
            # 任务阶段104
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/temporaryTask/production/main/publish104/{$main_task_id}",
                'method' => CurlHelper::PUT,
                'contents' => [
                    'paragraph_monitoring_id' => $request->get('paragraph_monitoring_id'),
                    'stage_104_message' => TextHelper::strip($request->get('message', '')),
                    'stage_104_intro' => TextHelper::strip($request->get('message', ''), true),
                ],
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            return response()->json(['message' => '下达成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 生成子任务页面
     */
    final public function getMakeMainTask105(int $main_task_id)
    {
        try {
            # 主任务详情
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/{$this->_spas_url}/{$main_task_id}",
                'method' => CurlHelper::GET,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            # 种类列表
            $categories = DB::table('categories')
                ->where('deleted_at', null)
                ->orderBy(request('order_by', 'id'), request('direction', 'asc'))
                ->get();

            return view('TemporaryTask.Production.Main.makeMainTask105', [
                'main_task' => $main_task_response['body']['data'],
                'categories' => $categories,
            ]);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * 保存任务内容到文件
     * @param Request $request
     * @param int $main_task_id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function postSaveMainTaskFile(Request $request, int $main_task_id)
    {
        try {
            # 重新组合参数
            $models = ['转辙机工区' => [], '继电器工区' => [], '综合工区' => []];
            foreach ($request->all() as $key => $value) {
                list($code1, $code2, $code3, $name1, $name2, $name3) = explode('丨', $key);
                switch (substr($code1, 0, 3)) {
                    case 'S03':
                        $workAreaName = '转辙机工区';
                        break;
                    case 'Q01':
                        $workAreaName = '继电器工区';
                        break;
                    default:
                        $workAreaName = '综合工区';
                        break;
                }
                $model = [
                    'code1' => $code1,
                    'code2' => $code2,
                    'code3' => $code3,
                    'name1' => $name1,
                    'name2' => $name2,
                    'name3' => $name3,
                    'number' => intval($value),
                    'fixed' => 0,
                ];
                $model['type'] = substr($code3, 0, 1) == 'S' ? 'part_models' : 'sub_models';
                $models[$workAreaName][] = $model;
            }

            $response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/{$this->_spas_url}/saveMainTaskFile/{$main_task_id}",
                'method' => CurlHelper::POST,
                'contents' => [
                    'models' => json_encode($models, 256)
                ],
            ]);
            if ($response['code'] > 399) return response()->json($response['body'], $response['code']);

            return response()->json(['message' => '成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 检修基地盯控干部确认任务完成
     */
    final public function putMakeMainTask201(Request $request, int $main_task_id)
    {
        try {
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/{$this->_spas_url}/publish201/{$main_task_id}",
                'method' => CurlHelper::PUT,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            return response()->json(['message' => '任务完成']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台连接失败'], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * 检修基地车间主任确认任务完成
     */
    final public function putMakeMainTask202(Request $request, int $main_task_id)
    {
        try {
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/{$this->_spas_url}/publish202/{$main_task_id}",
                'method' => CurlHelper::PUT,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            return response()->json(['message' => '任务完成']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台连接失败'], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * 电务段确认任务完成
     */
    final public function putMakeMainTask203(Request $request, int $main_task_id)
    {
        try {
            $main_task_response = CurlHelper::init([
                'headers' => $this->_auth,
                'url' => "{$this->_root_url}/{$this->_spas_url}/publish203/{$main_task_id}",
                'method' => CurlHelper::PUT,
            ]);
            if ($main_task_response['code'] > 399) return response()->json($main_task_response['body'], $main_task_response['code']);

            return response()->json(['message' => '任务完成']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台连接失败'], 500);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * 根据种类获取类型列表
     */
    final public function getEntireModelsByCategoryUniqueCode(string $category_unique_code)
    {
        try {
            $entire_models = DB::table('entire_models')->where('deleted_at', null)->where('is_sub_model', false)->where('category_unique_code', $category_unique_code);
            return request()->ajax() ? response()->json($entire_models->orderBy(request('order_by', 'id'), request('direction', 'asc'))->get()) : response()->make();
        } catch (\Throwable $th) {
            return request()->ajax() ? response()->json(['message' => $th->getMessage()], 500) : response()->make();
        }
    }

    /**
     * 根据类型获取部件类型列表
     */
    final public function getPartModelsByEntireModelUniqueCode(string $entire_model_unique_code)
    {
        try {
            $part_models = DB::table('part_models')->where('entire_model_unique_code', $entire_model_unique_code);
            return request()->ajax() ? response()->json($part_models->orderBy(request('order_by', 'id'), request('direction', 'asc'))->get()) : response()->make();
        } catch (\Throwable $th) {
            return request()->ajax() ? response()->json(['message' => $th->getMessage()], 500) : response()->make();
        }
    }

    /**
     * 根据类型获取子类列表
     */
    final public function getSubModelsByEntireModelUniqueCode(string $entire_model_unique_code)
    {
        try {
            $sub_models = DB::table('entire_models')->where('deleted_at', null)->where('is_sub_model', true)->where('parent_unique_code', $entire_model_unique_code);
            return request()->ajax() ? response()->json($sub_models->orderBy(request('order_by', 'id'), request('direction', 'asc'))->get()) : response()->make();
        } catch (\Throwable $th) {
            return request()->ajax() ? response()->json(['message' => $th->getMessage()], 500) : response()->make();
        }
    }

    /**
     * 根据代码获取部件类型详情
     */
    final public function getPartModelByUniqueCode(string $unique_code)
    {
        try {
            $part_model = DB::table('part_models as pm')
                ->select([
                    'pm.unique_code as code3',
                    'em.unique_code as code2',
                    'c.unique_code as code1',
                    'pm.name as name3',
                    'em.name as name2',
                    'c.name as name1',
                ])
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->where('pm.deleted_at', null)
                ->where('pm.unique_code', $unique_code)
                ->first();
            if (!$part_model) return response()->json(['message' => '部件类型不存在'], 404);

            return request()->ajax() ? response()->json($part_model) : response()->make();
        } catch (\Throwable $th) {
            return request()->ajax() ? response()->json(['message' => $th->getMessage()], 500) : response()->make();
        }
    }

    /**
     * 根据代码获取子类详情
     */
    final public function getSubModelByUniqueCode(string $unique_code)
    {
        try {
            $sub_model = DB::table('entire_models as sm')
                ->select([
                    'sm.unique_code as code3',
                    'em.unique_code as code2',
                    'c.unique_code as code1',
                    'sm.name as name3',
                    'em.name as name2',
                    'c.name as name1',
                ])
                ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->where('sm.deleted_at', null)
                ->where('sm.unique_code', $unique_code)
                ->first();
            if (!$sub_model) return response()->json(['message' => '子型不存在'], 404);

            return request()->ajax() ? response()->json($sub_model) : response()->make();
        } catch (\Throwable $th) {
            return request()->ajax() ? response()->json(['message' => $th->getMessage()], 500) : response()->make();
        }
    }

    /**
     * 更换车站
     * @param Request $request
     * @return JsonResponse
     */
    final public function postChangeStation(Request $request)
    {
        try {
            $full_fix = function () use ($request) {
                # 获取车站信息
                $maintain = Maintain::with(['Parent'])->where('unique_code', $request->get('stationUniqueCode'))->first();
                if (!$maintain) return response()->json(['message' => '所选车站不存在'], 404);

                # 删除原有型号
                DB::table('repair_base_full_fix_order_models')
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->delete();
                # 删除原有设备
                DB::table('repair_base_full_fix_order_entire_instances')
                    ->where('temporary_task_production_main_id', $request->get('mainTaskId'))
                    ->delete();

                # 获取车站下所有型号和设备数量
                $station_models = collect(DB::select("
select count(ei.model_name) as aggregate,
       ei.model_unique_code as model_unique_code,
       ei.model_name        as model_name
from entire_instances as ei
where ei.maintain_station_name = ?
  and ei.deleted_at is null
group by ei.model_unique_code, ei.model_name", [$maintain->name]));
                $station_models_with_unique_code = $station_models->pluck('aggregate', 'model_unique_code');
                $station_models_with_name = $station_models->pluck('model_name');

                $entire_models = EntireModel::with(['EntireModel', 'Category'])->whereIn('unique_code', $station_models_with_unique_code->keys()->all())->get();
                $part_models = PartModel::with(['EntireModel', 'Category'])->whereIn('unique_code', $station_models_with_unique_code->keys()->all())->get();
                $models = array_merge($entire_models->all(), $part_models->all());
                return response()->json([
                    'models' => array_pluck($models, 'name', 'unique_code'),
                    'station_models' => $station_models->pluck('model_name', 'model_unique_code')->all(),
                ]);

                $full_fix_model_install = [];  # 待添加型号
                $full_fix_entire_instances_insert = [];  # 待添加设备
                $now = date('Y-m-d H:i:s');

                # 写入该站下所有型号
                $a = [];
                foreach ($models as $model) {
                    $a[] = $model;
                    $work_area_id = 3;
                    if ($model->category_unique_code === 'S03') $work_area_id = 1;
                    if ($model->category_unique_code === 'Q01') $work_area_id = 2;
                    $full_fix_model_install[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'model_name' => $model->name,
                        'model_unique_code' => $model->unique_code,
                        'entire_model_name' => $model->EntireModel->name,
                        'entire_model_unique_code' => $model->EntireModel->unique_code,
                        'category_name' => $model->Category->name,
                        'category_unique_code' => $model->Category->unique_code,
                        'work_area_id' => $work_area_id,
                        'number' => $station_models_with_unique_code->get($model->unique_code),
                        'temporary_task_production_main_id' => $request->get('mainTaskId'),
                    ];
                }

                return response()->json([
                    'diff' => array_diff($station_models_with_name->all(), array_pluck($models, 'name')),
                    'station_models' => count($station_models),
                    'full_fix_model_install' => count($full_fix_model_install),
                    'models' => count($models)
                ]);

                # 写入该站下所有设备
                foreach (DB::table('entire_instances')
                             ->where('maintain_station_name', $maintain->name)
                             ->whereIn('model_unique_code', array_keys($station_models))
                             ->get() as $entire_instance) {
                    $entire_instance->scraping_time = Carbon::parse($entire_instance->scarping_at)->timestamp;
                    $entire_instance->picked = $entire_instance->scraping_time > time();

                    $full_fix_entire_instances_insert[] = [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'old_entire_instance_identity_code' => $entire_instance->identity_code,
                        'maintain_location_code' => $entire_instance->maintain_location_code,
                        'crossroad_number' => $entire_instance->crossroad_number,
                        'source' => $entire_instance->source,
                        'source_traction' => $entire_instance->source_traction,
                        'source_crossroad_number' => $entire_instance->source_crossroad_number,
                        'traction' => $entire_instance->traction,
                        'open_direction' => $entire_instance->open_direction,
                        'said_rod' => $entire_instance->said_rod,
                        'picked' => $entire_instance->picked,
                        'temporary_task_production_main_id' => $request->get('mainTaskId'),
                        'model_unique_code' => $entire_instance->model_unique_code,
                        'model_name' => $entire_instance->model_name,
                        'scraping_at' => $entire_instance->scarping_at,
                        'scraping_time' => $entire_instance->scraping_time,
                    ];
                }

                # 添加型号
                DB::table('repair_base_full_fix_order_models')->insert($full_fix_model_install);
                # 添加设备
                DB::table('repair_base_full_fix_order_entire_instances')->insert($full_fix_entire_instances_insert);

                return response()->json([
                    'message' => '添加成功',
                    'entire_models' => collect($full_fix_model_install)->groupBy('model_name')->keys()->sort()->all(),
                    'entire_models2' => collect($full_fix_entire_instances_insert)->groupBy('model_name')->keys()->sort()->all(),
                ]);
            };

            $func = strtolower($request->get('type'));
            return $$func();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => '意外错误', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }
}
