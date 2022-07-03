<?php

namespace App\Http\Controllers\V1\Basic;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

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
     * @return \Illuminate\Http\JsonResponse
     */
    final public function index()
    {
        $db = DB::table('factories')
            ->where('deleted_at', null)
            ->orderBy(request('order_by', 'id'), request('direction', 'desc'));
        $factories = request('page') ? $db->paginate(request('limit', 15)) : $db->get();
        return response()->json($factories);
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        try {
            $repeat = DB::table('factories')->where('unique_code', $request->get('unique_code'))->orWhere('name', $request->get('name'))->first();
            if ($repeat) return response()->json(['message' => "名称或编号重复：{$repeat->name}({$repeat->unique_code})"], 404);

            DB::table('factories')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'name' => $request->get('name'),
                'unique_code' => $request->get('unique_code'),
            ]);

            return response()->json(['message' => '新建成功']);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function show($unique_code)
    {
        try {
            $factory = DB::table('factories')->where('unique_code', $unique_code)->first();
            if (!$factory) return response()->json(['message' => '供应商不存在'], 404);

            return response()->json(['data' => $factory]);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function update(Request $request, $unique_code)
    {
        try {
            if (!DB::table('factories')->where('unique_code', $unique_code)->first()) return response()->json(['message' => '供应商不存在'], 404);

            $repeat = DB::table('factories')->where('name', $request->get('name'))->where('unique_code', '<>', $unique_code)->first();
            if ($repeat) return response()->json(['message' => '名称重复'], 404);

            DB::table('factories')->where('unique_code', $unique_code)->update(['name' => $request->get('name')]);

            return response()->json(['message' => '编辑成功']);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy($unique_code)
    {
        DB::table('factories')->where('unique_code', $unique_code)->delete();
        return response()->json(['message' => '删除成功']);
    }
}
