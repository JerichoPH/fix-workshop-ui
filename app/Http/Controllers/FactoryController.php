<?php

namespace App\Http\Controllers;

use App\Facades\ModelBuilderFacade;
use App\Http\Requests\V1\FactoryRequest;
use App\Model\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Jericho\BadRequestException;
use Jericho\CurlHelper;

class FactoryController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic/factory';
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
        $this->_root_url = "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = ["Username:{$this->_spas_username}", "Password:{$this->_spas_password}"];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Support\Collection|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            switch (request('type')) {
                case 'category_unique_code':
                    return DB::table('pivot_entire_model_and_factories')->whereIn(
                        'entire_model_unique_code',
                        DB::table('entire_models')->where('category_unique_code', request('category_unique_code'))->pluck('unique_code')
                    )
                        ->pluck('factory_name as name');
                default:
                    return ModelBuilderFacade::init(request(), Factory::with([]), ['name'])
                        ->extension(function ($builder) {
                            $builder
                                ->when(
                                    request('name'),
                                    function ($query, $name) {
                                        $query->where('name', 'like', "%{$name}%");
                                    }
                                )
                                ->orderBy('id');
                        })
                        ->all();
            }
        }

        $factories = ModelBuilderFacade::init(request(), Factory::with([]), ['name'])
            ->extension(function ($builder) {
                $builder
                    ->when(
                        request('name'),
                        function ($query, $name) {
                            $query->where('name', 'like', "%{$name}%");
                        }
                    )
                    ->orderBy('id');
            })
            ->pagination(50);
        return view('Factory.index', ['factories' => $factories]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $viewName = request()->ajax() ? 'create_ajax' : 'create';
        return view("Factory.{$viewName}");
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
            $v = Validator::make($request->all(), FactoryRequest::$RULES, FactoryRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            if (Factory::with([])->where("name", $request->get("name"))->exists()) {
                return Response::make("名称被占用", 403);
            }

            $last_factory = Factory::with([])->orderByDesc("unique_code")->first();
            $last_unique_code = intval($last_factory ? substr($last_factory->unique_code, -4) : "1");
            $new_unique_code = "P" . str_pad($last_unique_code + 1, "4", "0", STR_PAD_LEFT);

            $factory = new Factory;
            $factory
                ->fill([
                    "unique_code" => $new_unique_code,
                    "name" => $request->get("name"),
                ])
                ->saveOrFail();

            return Response::make('新建成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            //             return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
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
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $factory = Factory::findOrFail($id);
            return view('Factory.edit', ['factory' => $factory]);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return back()->with('danger', '意外错误');
        }
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
        try {
            $v = Validator::make($request->all(), FactoryRequest::$RULES, FactoryRequest::$MESSAGES);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            if (Factory::with([])->where("name", $request->get("name"))->where("id", "<>", $id)->exists()) {
                return Response::make("名称被占用", 403);
            }

            DB::transaction(function () use ($request, $id) {
                $factory = Factory::with([])->where("id", $id)->firstOrFail();
                $old_name = $factory->name;
                DB::table("entire_instances")->where("factory_name", $old_name)->update(["factory_name" => $request->get("name"),]);

                $factory->fill(["name" => $request->get("name"),])->saveOrFail();
            });

            return Response::make('编辑成功');
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
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $factory = Factory::findOrFail($id);
            $factory->delete();
            if (!$factory->trashed()) return Response::make('删除失败', 500);

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

    public function getBatch()
    {
        return view('Factory.batch');
    }

    public function postBatch(Request $request)
    {
        try {
            $excel = \App\Facades\FactoryFacade::batch($request, 'file');
            DB::table('factories')->insert($excel['success']);
            return back()->with('success', '导入成功');

        } catch (ModelNotFoundException $exception) {
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    '数据不存在：' . $exception->getMessage() :
                    '数据不存在', 404) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    '数据不存在：' . $exception->getMessage() :
                    '数据不存在');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return $request->ajax() ?
                response()->make(env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "意外错误", 500) :
                back()->withInput()->with('danger', env('APP_DEBUG') ?
                    "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                    "意外错误");
        }
    }

    /**
     * 从数据中台备份到本地
     */
    final public function getBackupFromSPAS()
    {
        try {
            # 同步供应商
            $factories_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => ['order_by' => 'id'],
            ]);
            if ($factories_response['code'] != 200) return response()->json($factories_response['body'], $factories_response['code']);

            # 写入供应商
            $insert_factories = [];
            foreach ($factories_response['body']['data'] as $datum) {
                $insert_factories[] = [
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'phone' => $datum['phone'],
                    'official_home_link' => $datum['official_home_link'],
                ];
            }
            if ($insert_factories) {
                DB::table('factories')->truncate();
                DB::table('factories')->insert($insert_factories);
            }

            return response()->json(['message' => '同步成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
