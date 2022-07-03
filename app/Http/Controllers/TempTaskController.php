<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Facades\TextFacade;
use App\Model\Account;
use Curl\Curl;
use CURLFile;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class TempTaskController extends Controller
{
    private $_curl = null;

    public function __construct()
    {
        $this->_curl = new Curl();
        $this->_curl->setHeader('Access-Key', env('GROUP_ACCESS_KEY'));
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            switch (request('target')) {
                case 'mineCreate':
                    $params = [
                        'nonce' => TextFacade::rand(),
//                        'status' => '101_UN_PUBLISH',
                        'initiator_paragraph_original_id' => session('account.id'),
                        'ordering' => 'id desc',
                        'page' => request('page', 1),
                    ];
                    $subTitle = '我创建的';
                    break;
                case 'minePrincipal':
                    $params = [
                        'nonce' => TextFacade::rand(),
                        'status' => [
                            'operator' => '<>',
                            'value' => '101_UN_PUBLISH'
                        ],
                        'principal_paragraph_original_id' => session('account.id'),
                        'ordering' => 'id desc',
                        'page' => request('page', 1),
                    ];
                    $subTitle = '我负责的';
                    break;
                default:
                    $params = [
                        'nonce' => TextFacade::rand(),
                        'status' => [
                            'operator' => '<>',
                            'value' => '101_UN_PUBLISH',
                        ],
                        'ordering' => 'id desc',
                        'page' => request('page', 1),
                    ];
                    $subTitle = '已发布';
                    break;
            }

            $sign = TextFacade::makeSign($params, env('GROUP_SECRET_KEY'));
            $this->_curl->setHeader('Sign', $sign);
            $this->_curl->get(env('GROUP_URL') . '/tempTask', $params);
            if ($this->_curl->error) {
                dd($this->_curl->response, $this->_curl->errorCode, $this->_curl->errorMessage);
                return back()->with('danger', $this->_curl->response->msg);
            }
            ['temp_tasks' => $tempTasks, 'temp_task_statuses' => $tempTaskStatuses] = (array)$this->_curl->response->data;


            $paginator = new LengthAwarePaginator(
                $tempTasks->data,
                $tempTasks->total,
                15,
                request('page', 1),
                ['path' => url('/tempTask'), 'pageName' => 'page',]
            );

            return view('TempTask.index', [
                'subTitle' => $subTitle,
                'tempTasks' => $paginator,
                'tempTaskStatuses' => (array)$tempTaskStatuses,
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $accounts = Account::with([])->whereIn('temp_task_position', ['ParagraphPrincipal', 'ParagraphCrew', 'ParagraphWorkshop', 'ParagraphEngineer'])->get();

            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . '/tempTask/create', $params);
            if ($this->_curl->error) return redirect('tempTask?page=' . request('page', 1))->with('danger', $this->_curl->response->msg);

            return view('TempTask.create', [
                'accounts' => $accounts,
                'tempTaskTypes' => (array)$this->_curl->response->temp_task_types,
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect('tempTask?page=' . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function store(Request $request)
    {
        try {
            # 新建任务
            $req = array_merge($request->except('files'), ['nonce' => TextFacade::rand()]);
            $req['description'] = str_replace("\r\n", "<br>", $req['description']);
            $req['description'] = str_replace("\r", "<br>", $req['description']);
            $req['description'] = str_replace("\n", "<br>", $req['description']);

            $this->_curl->setHeader('Sign', TextFacade::makeSign($req, env('GROUP_SECRET_KEY')));
            $this->_curl->post(env('GROUP_URL') . '/tempTask', $req);
            if ($this->_curl->error) return back()->withInput()->with('danger', $this->_curl->response->msg);
            ['temp_task' => $tempTask] = (array)$this->_curl->response->data;

            # 保存附件
            $files = [];
            $accessoryPath = storage_path("tempTask/accessory/");
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $saveFile = Storage::disk('tempTask')->put('', $file);
                    rename($accessoryPath . $saveFile, $accessoryPath . $file->getClientOriginalName());
                    $files[] = $accessoryPath . $file->getClientOriginalName();
                }
            }

            # 上传附件
            $uploadFiles = [];
            if ($files) {
                foreach ($files as $file) {
                    $params = [
                        'nonce' => TextFacade::rand(),
                        'temp_task_id' => $tempTask->id,
                        'uploader_id' => session('account.id'),
                        'file' => new CURLFile($file),
                    ];
                    $this->_curl->setHeaders(['Access-Key' => env('GROUP_ACCESS_KEY')]);
                    $this->_curl->post(env('GROUP_URL') . '/tempTaskAccessory', $params);
                    if (!$this->_curl->isHttpError() && !$this->_curl->isCurlError()) unlink($file);
                    $uploadFiles[] = basename($file);
                }
            }

            return redirect("/tempTask/{$tempTask->id}/edit")->with('success', '新建成功');
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * Display the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($id)
    {
        try {
            # 标记消息已读
            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/message/" . request('message_id'), $params);
            if ($this->_curl->error) return redirect('tempTask?page=' . request('page', 1))->withInput()->with('danger', $this->_curl->response->msg);

            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTask/{$id}", $params);
            if ($this->_curl->error) return redirect('tempTask?page=' . request('page', 1))->withInput()->with('danger', $this->_curl->response->msg);
            ['temp_task' => $tempTask, 'temp_task_statuses' => $tempTaskStatuses, 'temp_task_modes' => $tempTaskModes] = (array)$this->_curl->response->data;

            $workAreas = Account::$WORK_AREAS;  # 工区
            $workAreaPrincipals = Account::with([])->where('temp_task_position', 'WorkshopWorkArea')->get()->groupBy('work_area')->toArray();  # 获取工区负责人

            $statisticsRootDir = storage_path('app/basicInfo');  # 现场车间、车站
            if (!file_exists("{$statisticsRootDir}/stations.json")) return back()->with('danger', '现场车间、车站缓存不存在');
            $sceneWorkshops = file_get_contents("{$statisticsRootDir}/stations.json");

            # 标记任务进行中
            $params = ['nonce' => TextFacade::rand(), 'status' => '201_PROCESSING'];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->put(env('GROUP_URL') . "/tempTask/{$id}/processing", $params);

            return view('TempTask.show', [
                'tempTask' => $tempTask,
                'tempTaskStatuses' => (array)$tempTaskStatuses,
                'tempTaskModes' => (array)$tempTaskModes,
                'tempTaskAccessories' => $tempTask->accessories,
                'workAreaPrincipalsAsJson' => json_encode($workAreaPrincipals),
                'workAreasAsJson' => json_encode($workAreas),
                'sceneWorkshopsAsJson' => $sceneWorkshops,
            ]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect("tempTask?page=" . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($id)
    {
        try {
            $accounts = Account::with([])->whereIn('temp_task_position', ['ParagraphPrincipal', 'ParagraphCrew', 'ParagraphWorkshop', 'ParagraphEngineer'])->get();

            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/tempTask/{$id}", $params);
            ['temp_task' => $tempTask, 'temp_task_statuses' => $tempTaskStatuses, 'temp_task_types' => $tempTaskTypes] = (array)$this->_curl->response->data;

            return view('TempTask.edit', [
                'tempTask' => $tempTask,
                'tempTaskStatuses' => (array)$tempTaskStatuses,
                'tempTaskTypes' => (array)$tempTaskTypes,
                'accounts' => $accounts,
            ]);
        } catch (\Throwable $e) {
\App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return redirect("tempTask?page=" . request('page', 1))->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function update(Request $request, $id)
    {
        try {
            # 新建任务
            $sign = TextFacade::makeSign($request->except('files'), env('GROUP_SECRET_KEY'));
            $this->_curl->setHeader('Sign', $sign);
            $this->_curl->put(env('GROUP_URL') . "/tempTask/{$id}", $request->except('files'));
            if ($this->_curl->error) return back()->withInput()->with('danger', $this->_curl->response->msg);

            # 保存附件
            $files = [];
            $accessoryPath = storage_path("tempTask/accessory/");
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $saveFile = Storage::disk('tempTask')->put('', $file);
                    rename($accessoryPath . $saveFile, $accessoryPath . $file->getClientOriginalName());
                    $files[] = $accessoryPath . $file->getClientOriginalName();
                }
            }

            # 上传附件
            $uploadFiles = [];
            if ($files) {
                foreach ($files as $file) {
                    $params = [
                        'nonce' => TextFacade::rand(),
                        'temp_task_id' => $id,
                        'uploader_id' => session('account.id'),
                        'file' => new CURLFile($file),
                    ];
                    $this->_curl->setHeaders(['Access-Key' => env('GROUP_ACCESS_KEY')]);
                    $this->_curl->post(env('GROUP_URL') . '/tempTaskAccessory', $params);
                    if (!$this->_curl->isHttpError() && !$this->_curl->isCurlError()) unlink($file);
                    $uploadFiles[] = basename($file);
                }
            }

            return back()->with('success', '保存成功');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        try {
            $params = ['nonce' => TextFacade::rand()];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->delete(env('GROUP_URL') . "/tempTask/{$id}", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);
            return JsonResponseFacade::deleted();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 发布任务
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function putPublish(Request $request, int $id)
    {
        try {
            $params = ['nonce' => TextFacade::rand()];
            $sign = TextFacade::makeSign($params, env('GROUP_SECRET_KEY'));
            $this->_curl->setHeader('Sign', $sign);
            $this->_curl->put(env('GROUP_URL') . "/tempTask/{$id}/publish", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg, (array)$this->_curl->response);

            return JsonResponseFacade::data($this->_curl->response, '发布成功');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 电务段验收
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function putCAA(Request $request, int $id)
    {
        try {
            $params = [
                'nonce' => TextFacade::rand(),
                'paragraph_caa_message' => $request->get('paragraphCAAMessage'),
                'status' => '501_PARAGRAPH_CAA',
                'finish_at' => date('Y-m-d'),
                'is_finished' => true,
            ];
            $this->_curl->setHeader('Sign', TextFacade::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->put(env('GROUP_URL') . "/tempTask/{$id}/caa", $params);
            if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg);

            return JsonResponseFacade::updated('操作成功');
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
