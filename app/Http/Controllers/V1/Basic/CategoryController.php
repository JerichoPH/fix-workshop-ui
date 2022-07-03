<?php

namespace App\Http\Controllers\V1\Basic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\BadRequestException;
use Jericho\CurlHelper;

class CategoryController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic/category';
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
        $db = DB::table('categories')
            ->where('deleted_at', null)
            ->orderBy(request('order_by', 'id'), request('direction', 'desc'));
        $categories = request('page') ? $db->paginate(request('limit', 15)) : $db->get();
        return response()->json($categories);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
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
            $category_repeat = DB::table('categories')->where('deleted_at', null)->where('unique_code', $request->get('unique_code'))->first();
            if ($category_repeat) return response()->json(['message' => '种类代码重复'], 403);

            DB::table('categories')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'name' => $request->get('name'),
                'unique_code' => $request->get('unique_code'),
                'race_unique_code' => substr($request->get('unique_code'), 0, 1) === 'S' ? 1 : 2,
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
        $category = DB::table('categories')
            ->where('deleted_at', null)
            ->where('unique_code', $unique_code)
            ->first();

        if (!$category) return response()->json(['message' => '种类不存在'], 404);
        return response()->json(['data' => $category]);
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
            $category = DB::table('categories')->where('deleted_at', null)->where('unique_code', $unique_code)->first();
            if (!$category) return response()->json(['message' => '种类不存在'], 404);

            DB::table('categories')
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
        DB::table('categories')->where('unique_code', $unique_code)->delete();
        return response()->json(['message' => '删除成功']);
    }

    public function getInit()
    {
        try {
            $categories_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
            ]);
            if ($categories_response['code'] != 200) return response()->json($categories_response['body'], $categories_response['code']);

            $insert = [];
            $current_time = date('Y-m-d H:i:s');
            foreach ($categories_response['body']['data'] as $datum) {
                $insert[] = [
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'race_unique_code' => $datum['race_unique_code'],
                ];
            }

            DB::table('categories')->truncate();
            DB::table('categories')->insert($insert);

            return response()->json(['message' => '备份成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
