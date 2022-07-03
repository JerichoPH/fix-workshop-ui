<?php

namespace App\Http\Controllers\V1\Basic;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PartModelController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic/partModel';
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
    public function index()
    {
        $db = DB::table('part_models')
            ->where('deleted_at', null)
            ->orderBy(request('order_by', 'id'), request('direction', 'desc'));
        $part_models = request('page') ? $db->paginate(request('limit', 15)) : $db->get();
        return response()->json($part_models);
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $entire_model_repeat = DB::table('part_models')->where('deleted_at', null)->where('unique_code', $request->get('unique_code'))->first();
            if ($entire_model_repeat) return response()->json(['message' => '部件类型代码重复'], 403);

            DB::table('part_models')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'name' => $request->get('name'),
                'unique_code' => $request->get('unique_code'),
                'category_unique_code' => $request->get('category_unique_code'),
                'entire_model_unique_code' => $request->get('entire_model_unique_code'),
            ]);

            return response()->json(['message' => '新建成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($unique_code)
    {
        $part_model = DB::table('part_models')
            ->where('deleted_at', null)
            ->where('unique_code', $unique_code)
            ->first();
        if (!$part_model) return response()->json(['message' => '部件类型不存在'], 404);
        return response()->json(['data' => $part_model]);
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
     * @param Request $request
     * @param $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $unique_code)
    {
        try {
            $part_model = DB::table('part_models')->where('deleted_at', null)->where('unique_code', $unique_code)->first();
            if (!$part_model) return response()->json(['message' => '部件类型不存在'], 404);

            DB::table('part_models')
                ->where('deleted_at', null)
                ->where('unique_code', $unique_code)
                ->update(['updated_at' => date('Y-m-d H:i:s'), 'name' => $request->get('name')]);

            return response()->json(['message' => '编辑成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($unique_code)
    {
        DB::table('part_models')->where('unique_code', $unique_code)->delete();
        return response()->json(['message' => '删除成功']);
    }
}
